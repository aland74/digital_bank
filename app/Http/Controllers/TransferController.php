<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PendingTransfer;
use App\Models\Notification;
use App\Models\AuditLog;
use App\Models\BankSetting;
use App\Services\AccountService;
use App\Services\DistributedDatabaseService;
use App\Services\ExchangeRateService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function create(Request $request)
    {
        $accounts = $request->user()->accounts()->active()->get();
        $beneficiaries = $request->user()->beneficiaries()->orderBy('is_favorite', 'desc')->get();

        return view('transfers.create', compact('accounts', 'beneficiaries'));
    }

    public function confirm(Request $request)
    {
        // Handle GET requests (e.g. redirected back from store due to validation errors)
        if ($request->isMethod('get')) {
            $request->session()->reflash();
            return redirect()->route('transfers.create')->withInput();
        }

        $validated = $request->validate([
            'from_account_id' => 'required|integer',
            'to_account_number' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        // Sender's account — from their branch DB (current default)
        $fromAccount = Account::findOrFail($validated['from_account_id']);
        $this->authorize('view', $fromAccount);

        // Recipient's account — search HQ since recipient may be on a DIFFERENT branch
        $toAccount = Account::on(DistributedDatabaseService::getHqConnection())
            ->where('account_number', $validated['to_account_number'])
            ->first();

        if (!$toAccount) {
            return back()->withErrors(['to_account_number' => 'Recipient account not found.'])->withInput();
        }

        if ($fromAccount->id === $toAccount->id) {
            return back()->withErrors(['to_account_number' => 'Cannot transfer to the same account.'])->withInput();
        }

        if ($fromAccount->user_id === $toAccount->user_id) {
            return back()->withErrors(['to_account_number' => 'Cannot transfer to your own account. Use internal transfer instead.'])->withInput();
        }

        if ($fromAccount->available_balance < $validated['amount']) {
            return back()->withErrors(['amount' => 'Insufficient funds.'])->withInput();
        }

        return view('transfers.confirm', [
            'fromAccount' => $fromAccount,
            'toAccount' => $toAccount,
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?? '',
        ]);
    }

    /**
     * Create a pending transfer (hold funds, notify receiver).
     */
    public function store(Request $request, TransactionService $transactionService)
    {
        $validated = $request->validate([
            'from_account_id' => 'required|integer',
            'to_account_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        // Sender's account from their branch
        $fromAccount = Account::findOrFail($validated['from_account_id']);
        $this->authorize('view', $fromAccount);

        // Recipient's account from HQ (cross-branch lookup)
        $toAccount = Account::on(DistributedDatabaseService::getHqConnection())
            ->findOrFail($validated['to_account_id']);

        // Fraud detection
        $fraudService = app(\App\Services\FraudDetectionService::class);
        $fraudResult = $fraudService->evaluateTransfer($fromAccount->user, $validated['amount']);

        if ($fraudResult['status'] === 'blocked') {
            return back()->withErrors(['transfer' => $fraudResult['message']])->withInput();
        }

        // Check available balance
        if ($fromAccount->available_balance < $validated['amount']) {
            return back()->withErrors(['transfer' => 'Insufficient available funds.'])->withInput();
        }

        try {
            // Hold funds on sender's account
            $transactionService->holdFunds($fromAccount, $validated['amount']);

            // Lock exchange rate at time of transfer creation
            $exchangeRate = null;
            if ($fromAccount->currency !== $toAccount->currency) {
                $exchangeRate = ExchangeRateService::getRate();
            }

            // Create pending transfer
            $expiryHours = BankSetting::transferExpiryHours();
            $pendingTransfer = PendingTransfer::create([
                'reference_number' => PendingTransfer::generateReference(),
                'sender_account_id' => $fromAccount->id,
                'receiver_account_id' => $toAccount->id,
                'sender_user_id' => $fromAccount->user_id,
                'receiver_user_id' => $toAccount->user_id,
                'amount' => $validated['amount'],
                'currency' => $fromAccount->currency,
                'exchange_rate' => $exchangeRate,
                'description' => $validated['description'] ?? '',
                'status' => 'pending',
                'expires_at' => now()->addHours($expiryHours),
                'ip_address' => $request->ip(),
            ]);

            // Write PendingTransfer to receiver's branch so they can see it
            // (SyncsWithHQ only syncs to HQ, not to other branches)
            $this->writePendingTransferToReceiverBranch($pendingTransfer);

            // Notify receiver (write directly to receiver's branch and HQ)
            $this->notifyReceiverOfTransfer($pendingTransfer);

            // Notify sender (on current branch — SyncsWithHQ replicates)
            Notification::create([
                'user_id' => $fromAccount->user_id,
                'title' => 'Transfer Request Sent',
                'message' => "Your transfer of \${$validated['amount']} to {$toAccount->user->name} is pending acceptance. Funds have been held.",
                'type' => 'info',
                'icon' => '📤',
                'action_url' => route('transfers.pending'),
                'data' => ['pending_transfer_id' => $pendingTransfer->id],
            ]);

            AuditLog::log('transfer_initiated', [
                'model_type' => 'PendingTransfer',
                'model_id' => $pendingTransfer->id,
                'severity' => $validated['amount'] > 5000 ? 'high' : 'medium',
                'new_values' => [
                    'amount' => $validated['amount'],
                    'from' => $fromAccount->account_number,
                    'to' => $toAccount->account_number,
                    'reference' => $pendingTransfer->reference_number,
                ],
            ]);

            return redirect()->route('transfers.success', ['reference' => $pendingTransfer->reference_number]);
        } catch (\Exception $e) {
            return back()->withErrors(['transfer' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Sync PendingTransfer status changes to HQ and sender's branch.
     * When receiver accepts/declines on their branch, other DBs need the update too.
     */
    private function syncPendingTransferStatus(PendingTransfer $pendingTransfer, array $changes): void
    {
        $hqConnection = DistributedDatabaseService::getHqConnection();

        // Update HQ
        try {
            DB::connection($hqConnection)->table('pending_transfers')
                ->where('id', $pendingTransfer->id)
                ->update($changes);
        } catch (\Exception $e) {
            \Log::error("Failed to sync PendingTransfer status to HQ: " . $e->getMessage());
        }

        // Update sender's branch
        $senderBranch = DistributedDatabaseService::findUserBranchById($pendingTransfer->sender_user_id);
        if ($senderBranch) {
            $senderConnection = DistributedDatabaseService::connectionForBranch($senderBranch);
            try {
                DB::connection($senderConnection)->table('pending_transfers')
                    ->where('id', $pendingTransfer->id)
                    ->update($changes);
            } catch (\Exception $e) {
                \Log::error("Failed to sync PendingTransfer status to sender branch: " . $e->getMessage());
            }
        }
    }

    /**
     * Write the PendingTransfer record to the receiver's branch DB
     * so they can see it on their pending transfers page.
     */
    private function writePendingTransferToReceiverBranch(PendingTransfer $pendingTransfer): void
    {
        $receiverBranch = DistributedDatabaseService::findUserBranchById($pendingTransfer->receiver_user_id);
        if (!$receiverBranch) return;

        $connection = DistributedDatabaseService::connectionForBranch($receiverBranch);

        try {
            DB::connection($connection)->table('pending_transfers')->insertOrIgnore([
                'id' => $pendingTransfer->id,
                'reference_number' => $pendingTransfer->reference_number,
                'sender_account_id' => $pendingTransfer->sender_account_id,
                'receiver_account_id' => $pendingTransfer->receiver_account_id,
                'sender_user_id' => $pendingTransfer->sender_user_id,
                'receiver_user_id' => $pendingTransfer->receiver_user_id,
                'amount' => $pendingTransfer->amount,
                'currency' => $pendingTransfer->currency,
                'exchange_rate' => $pendingTransfer->exchange_rate,
                'description' => $pendingTransfer->description,
                'status' => $pendingTransfer->status,
                'expires_at' => $pendingTransfer->expires_at->format('Y-m-d H:i:s'),
                'ip_address' => $pendingTransfer->ip_address,
                'created_at' => $pendingTransfer->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $pendingTransfer->updated_at->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to write PendingTransfer to receiver branch: " . $e->getMessage());
        }
    }

    /**
     * Write a notification to the receiver's branch DB and HQ.
     */
    private function notifyReceiverOfTransfer(PendingTransfer $pendingTransfer): void
    {
        // Determine receiver's branch
        $receiverBranch = DistributedDatabaseService::findUserBranchById($pendingTransfer->receiver_user_id);

        // Load sender name from HQ
        $senderName = DB::connection(DistributedDatabaseService::getHqConnection())
            ->table('users')
            ->where('id', $pendingTransfer->sender_user_id)
            ->value('name') ?? 'Someone';

        $notifData = [
            'user_id' => $pendingTransfer->receiver_user_id,
            'title' => 'Incoming Transfer Request',
            'message' => "{$senderName} wants to send you \${$pendingTransfer->amount}. Accept or decline this transfer.",
            'type' => 'transfer_request',
            'icon' => '📨',
            'action_url' => route('transfers.pending'),
            'data' => json_encode([
                'action' => 'transfer_accept_decline',
                'pending_transfer_id' => $pendingTransfer->id,
                'sender_name' => $senderName,
                'amount' => $pendingTransfer->amount,
            ]),
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Write to receiver's branch DB
        if ($receiverBranch) {
            try {
                $connection = DistributedDatabaseService::connectionForBranch($receiverBranch);
                DB::connection($connection)->table('notifications')->insert($notifData);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to write notification to receiver branch: " . $e->getMessage());
            }
        }

        // Write to HQ
        try {
            DB::connection(DistributedDatabaseService::getHqConnection())->table('notifications')->insert($notifData);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to write notification to HQ: " . $e->getMessage());
        }
    }

    public function success(Request $request)
    {
        $reference = $request->query('reference');
        $pendingTransfer = PendingTransfer::where('reference_number', $reference)->first();

        return view('transfers.success', compact('reference', 'pendingTransfer'));
    }

    /**
     * Show pending transfers for the user.
     */
    public function pending(Request $request)
    {
        $userId = $request->user()->id;

        // Auto-expire old pending transfers
        $this->expireOldTransfers();

        // Query from HQ since pending transfers are stored there
        $hqConnection = DistributedDatabaseService::getHqConnection();

        $incoming = PendingTransfer::on($hqConnection)
            ->incoming($userId)
            ->with(['senderUser', 'senderAccount', 'receiverAccount'])
            ->orderBy('created_at', 'desc')
            ->get();

        $outgoing = PendingTransfer::on($hqConnection)
            ->outgoing($userId)
            ->with(['receiverUser', 'senderAccount', 'receiverAccount'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('transfers.pending', compact('incoming', 'outgoing'));
    }

    /**
     * Receiver accepts a pending transfer.
     */
    public function accept(Request $request, PendingTransfer $pendingTransfer, TransactionService $transactionService)
    {
        // Load from HQ if not found on current connection
        if (!$pendingTransfer->exists) {
            $pendingTransfer = PendingTransfer::on(DistributedDatabaseService::getHqConnection())
                ->find($request->route('pendingTransfer')?->id ?? $request->id);
            if (!$pendingTransfer) {
                return back()->with('error', 'Transfer not found.');
            }
        }

        // Only receiver can accept
        if ($pendingTransfer->receiver_user_id !== $request->user()->id) {
            abort(403);
        }

        if (!$pendingTransfer->canBeAccepted()) {
            return back()->with('error', 'This transfer can no longer be accepted.');
        }

        try {
            // Load sender account — try sender's branch first, fall back to HQ
            $senderAccount = AccountService::findSenderAccount(
                $pendingTransfer->sender_account_id,
                $pendingTransfer->sender_user_id
            );

            // Load receiver account from current branch
            $receiverAccount = Account::findOrFail($pendingTransfer->receiver_account_id);

            // Execute the actual transfer with locked exchange rate
            $result = $transactionService->executeHeldTransfer(
                $senderAccount,
                $receiverAccount,
                $pendingTransfer->amount,
                $pendingTransfer->description ?: 'Transfer from ' . $pendingTransfer->senderUser->name,
                $pendingTransfer->exchange_rate
            );

            // Update pending transfer status
            $pendingTransfer->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            // Sync status to HQ and sender's branch
            $this->syncPendingTransferStatus($pendingTransfer, [
                'status' => 'accepted',
                'accepted_at' => now()->format('Y-m-d H:i:s'),
            ]);

            // Send notifications
            Notification::transferAccepted($pendingTransfer);

            AuditLog::log('transfer_accepted', [
                'model_type' => 'PendingTransfer',
                'model_id' => $pendingTransfer->id,
                'severity' => 'medium',
                'new_values' => [
                    'amount' => $pendingTransfer->amount,
                    'reference' => $pendingTransfer->reference_number,
                ],
            ]);

            $currency = \App\Models\Currency::where('code', $pendingTransfer->currency)->first();
            $symbol = $currency?->symbol ?? $pendingTransfer->currency;
            $decimals = $currency?->decimal_places ?? 2;
            return back()->with('success', "Transfer accepted! {$symbol} " . number_format($pendingTransfer->amount, $decimals) . " has been added to your account.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process transfer: ' . $e->getMessage());
        }
    }

    /**
     * Receiver declines a pending transfer.
     */
    public function decline(Request $request, PendingTransfer $pendingTransfer, TransactionService $transactionService)
    {
        // Load from HQ if not found on current connection
        if (!$pendingTransfer->exists) {
            $pendingTransfer = PendingTransfer::on(DistributedDatabaseService::getHqConnection())
                ->find($request->route('pendingTransfer')?->id ?? $request->id);
            if (!$pendingTransfer) {
                return back()->with('error', 'Transfer not found.');
            }
        }

        if ($pendingTransfer->receiver_user_id !== $request->user()->id) {
            abort(403);
        }

        if (!$pendingTransfer->isPending()) {
            return back()->with('error', 'This transfer can no longer be declined.');
        }

        try {
            // Load sender account — try sender's branch first, fall back to HQ
            $senderAccount = AccountService::findSenderAccount(
                $pendingTransfer->sender_account_id,
                $pendingTransfer->sender_user_id
            );

            // Release held funds
            $transactionService->releaseHold($senderAccount, $pendingTransfer->amount);

            $pendingTransfer->update([
                'status' => 'declined',
                'declined_at' => now(),
            ]);

            // Sync status to HQ and sender's branch
            $this->syncPendingTransferStatus($pendingTransfer, [
                'status' => 'declined',
                'declined_at' => now()->format('Y-m-d H:i:s'),
            ]);

            Notification::transferDeclined($pendingTransfer);

            AuditLog::log('transfer_declined', [
                'model_type' => 'PendingTransfer',
                'model_id' => $pendingTransfer->id,
                'severity' => 'low',
            ]);

            return back()->with('success', 'Transfer declined. Funds have been returned to sender.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to decline transfer: ' . $e->getMessage());
        }
    }

    /**
     * Sender cancels a pending transfer.
     */
    public function cancel(Request $request, PendingTransfer $pendingTransfer, TransactionService $transactionService)
    {
        // Load from HQ if not found on current connection
        if (!$pendingTransfer->exists) {
            $pendingTransfer = PendingTransfer::on(DistributedDatabaseService::getHqConnection())
                ->find($request->route('pendingTransfer')?->id ?? $request->id);
            if (!$pendingTransfer) {
                return back()->with('error', 'Transfer not found.');
            }
        }

        if ($pendingTransfer->sender_user_id !== $request->user()->id) {
            abort(403);
        }

        if (!$pendingTransfer->canBeCancelled()) {
            return back()->with('error', 'This transfer can no longer be cancelled.');
        }

        // Release held funds
        $transactionService->releaseHold($pendingTransfer->senderAccount, $pendingTransfer->amount);

        $pendingTransfer->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Sync status to HQ and receiver's branch
        $this->syncPendingTransferStatus($pendingTransfer, [
            'status' => 'cancelled',
            'cancelled_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $currency = \App\Models\Currency::where('code', $pendingTransfer->currency)->first();
        $symbol = $currency?->symbol ?? $pendingTransfer->currency;
        $decimals = $currency?->decimal_places ?? 2;
        $amountFormatted = $symbol . ' ' . number_format($pendingTransfer->amount, $decimals);

        Notification::notifyUserOnBranch($pendingTransfer->receiver_user_id, [
            'user_id' => $pendingTransfer->receiver_user_id,
            'title' => 'Transfer Cancelled',
            'message' => "{$pendingTransfer->senderUser->name} cancelled their transfer of {$amountFormatted}.",
            'type' => 'info',
            'icon' => '↩️',
            'is_read' => false,
        ]);

        return back()->with('success', 'Transfer cancelled. Funds have been released.');
    }

    /**
     * Auto-expire old pending transfers and release holds.
     */
    private function expireOldTransfers(): void
    {
        $expired = PendingTransfer::expired()
            ->with(['senderAccount', 'receiverUser'])
            ->limit(50)
            ->get();

        $transactionService = app(TransactionService::class);

        foreach ($expired as $transfer) {
            try {
                $transactionService->releaseHold($transfer->senderAccount, $transfer->amount);
                $transfer->update(['status' => 'expired']);

                // Sync status to HQ and receiver's branch
                $this->syncPendingTransferStatus($transfer, [
                    'status' => 'expired',
                ]);

                $receiverName = $transfer->receiverUser?->name ?? 'Unknown';

                Notification::create([
                    'user_id' => $transfer->sender_user_id,
                    'title' => 'Transfer Expired',
                    'message' => "Your pending transfer of {$transfer->currency} {$transfer->amount} to {$receiverName} has expired. Funds have been released.",
                    'type' => 'warning',
                    'icon' => '⏰',
                ]);
            } catch (\Exception $e) {
                // Log error but continue processing other transfers
                \Log::warning("Failed to expire transfer {$transfer->reference_number}: {$e->getMessage()}");
            }
        }
    }

    // ── Currency Conversion ─────────────────────────────────────

    /**
     * Show the currency conversion form (USD <-> IQD between own accounts).
     */
    public function convertForm()
    {
        $user = auth()->user();
        $accounts = $user->accounts()->active()->get();

        $usdAccount = $accounts->where('currency', 'USD')->first();
        $iqdAccount = $accounts->where('currency', 'IQD')->first();

        if (!$usdAccount || !$iqdAccount) {
            return back()->with('error', 'You need both USD and IQD accounts to convert currency.');
        }

        $exchangeRate = \App\Services\ExchangeRateService::getRateInfo();

        return view('transfers.convert', compact('usdAccount', 'iqdAccount', 'exchangeRate'));
    }

    /**
     * Execute currency conversion between own accounts.
     */
    public function convert(Request $request, TransactionService $transactionService)
    {
        $validated = $request->validate([
            'from_currency' => 'required|in:USD,IQD',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = $request->user();
        $accounts = $user->accounts()->active()->get();

        $fromCurrency = $validated['from_currency'];
        $toCurrency = $fromCurrency === 'USD' ? 'IQD' : 'USD';

        $fromAccount = $accounts->where('currency', $fromCurrency)->first();
        $toAccount = $accounts->where('currency', $toCurrency)->first();

        if (!$fromAccount || !$toAccount) {
            return back()->with('error', 'You need both USD and IQD accounts.');
        }

        $amount = $validated['amount'];

        if ($fromAccount->available_balance < $amount) {
            $currency = \App\Models\Currency::where('code', $fromCurrency)->first();
            return back()->with('error', "Insufficient balance. Available: {$currency->symbol}" . number_format($fromAccount->available_balance, $currency->decimal_places));
        }

        try {
            $result = $transactionService->transfer(
                $fromAccount,
                $toAccount,
                $amount,
                "Currency conversion: {$fromCurrency} to {$toCurrency}"
            );

            $convertedAmount = $result['converted_amount'] ?? $amount;
            $rate = $result['exchange_rate'] ?? \App\Services\ExchangeRateService::getRate();

            $fromCurrencyModel = \App\Models\Currency::where('code', $fromCurrency)->first();
            $toCurrencyModel = \App\Models\Currency::where('code', $toCurrency)->first();

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Currency Converted',
                'message' => "Converted {$fromCurrencyModel->symbol}" . number_format($amount, $fromCurrencyModel->decimal_places) . " to {$toCurrencyModel->symbol}" . number_format($convertedAmount, $toCurrencyModel->decimal_places) . " @ " . number_format($rate, 2),
                'type' => 'success',
                'icon' => '🔄',
                'action_url' => route('dashboard'),
            ]);

            return redirect()->route('dashboard')->with('success', "Converted {$fromCurrencyModel->symbol}" . number_format($amount, $fromCurrencyModel->decimal_places) . " to {$toCurrencyModel->symbol}" . number_format($convertedAmount, $toCurrencyModel->decimal_places));
        } catch (\Exception $e) {
            return back()->with('error', 'Conversion failed: ' . $e->getMessage());
        }
    }
}

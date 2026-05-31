<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\BankSetting;
use App\Models\Notification;
use App\Models\PendingTransfer;
use App\Services\AccountService;
use App\Services\DistributedDatabaseService;
use App\Services\ExchangeRateService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferApiController extends Controller
{
    /**
     * List pending transfers (incoming + outgoing) for the user.
     */
    public function pending(Request $request)
    {
        $userId = $request->user()->id;

        // Auto-expire old pending transfers
        $this->expireOldTransfers();

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

        return response()->json([
            'incoming' => $incoming,
            'outgoing' => $outgoing,
        ]);
    }

    /**
     * Create a pending transfer (hold funds, notify receiver).
     */
    public function store(Request $request, TransactionService $transactionService)
    {
        $validated = $request->validate([
            'from_account_id' => 'required|integer',
            'to_account_number' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        // Sender's account from their branch
        $fromAccount = Account::findOrFail($validated['from_account_id']);
        if ($fromAccount->user_id !== $request->user()->id) {
            abort(403);
        }

        // Recipient's account — search HQ since recipient may be on a DIFFERENT branch
        $toAccount = Account::on(DistributedDatabaseService::getHqConnection())
            ->where('account_number', $validated['to_account_number'])
            ->first();

        if (!$toAccount) {
            return response()->json(['message' => 'Recipient account not found.'], 404);
        }

        if ($fromAccount->id === $toAccount->id) {
            return response()->json(['message' => 'Cannot transfer to the same account.'], 422);
        }

        if ($fromAccount->user_id === $toAccount->user_id) {
            return response()->json(['message' => 'Cannot transfer to your own account. Use internal transfer instead.'], 422);
        }

        if ($fromAccount->available_balance < $validated['amount']) {
            return response()->json(['message' => 'Insufficient funds.'], 422);
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

            return response()->json([
                'message' => 'Transfer initiated. Funds have been held pending acceptance.',
                'pending_transfer' => $pendingTransfer->fresh(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Accept a pending transfer (receiver only).
     */
    public function accept(Request $request, $id, TransactionService $transactionService)
    {
        $pendingTransfer = $this->findPendingTransfer($id);
        if (!$pendingTransfer) {
            return response()->json(['message' => 'Transfer not found.'], 404);
        }

        if ($pendingTransfer->receiver_user_id !== $request->user()->id) {
            abort(403);
        }

        if (!$pendingTransfer->canBeAccepted()) {
            return response()->json(['message' => 'This transfer can no longer be accepted.'], 422);
        }

        try {
            $senderAccount = AccountService::findSenderAccount(
                $pendingTransfer->sender_account_id,
                $pendingTransfer->sender_user_id
            );

            $receiverAccount = Account::findOrFail($pendingTransfer->receiver_account_id);

            $result = $transactionService->executeHeldTransfer(
                $senderAccount,
                $receiverAccount,
                $pendingTransfer->amount,
                $pendingTransfer->description ?: 'Transfer from ' . $pendingTransfer->senderUser->name,
                $pendingTransfer->exchange_rate
            );

            $pendingTransfer->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            $this->syncPendingTransferStatus($pendingTransfer, [
                'status' => 'accepted',
                'accepted_at' => now()->format('Y-m-d H:i:s'),
            ]);

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

            return response()->json([
                'message' => 'Transfer accepted successfully.',
                'pending_transfer' => $pendingTransfer->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to process transfer: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Decline a pending transfer (receiver only).
     */
    public function decline(Request $request, $id, TransactionService $transactionService)
    {
        $pendingTransfer = $this->findPendingTransfer($id);
        if (!$pendingTransfer) {
            return response()->json(['message' => 'Transfer not found.'], 404);
        }

        if ($pendingTransfer->receiver_user_id !== $request->user()->id) {
            abort(403);
        }

        if (!$pendingTransfer->isPending()) {
            return response()->json(['message' => 'This transfer can no longer be declined.'], 422);
        }

        try {
            $senderAccount = AccountService::findSenderAccount(
                $pendingTransfer->sender_account_id,
                $pendingTransfer->sender_user_id
            );

            $transactionService->releaseHold($senderAccount, $pendingTransfer->amount);

            $pendingTransfer->update([
                'status' => 'declined',
                'declined_at' => now(),
            ]);

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

            return response()->json([
                'message' => 'Transfer declined. Funds have been returned to sender.',
                'pending_transfer' => $pendingTransfer->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to decline transfer: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Cancel a pending transfer (sender only).
     */
    public function cancel(Request $request, $id, TransactionService $transactionService)
    {
        $pendingTransfer = $this->findPendingTransfer($id);
        if (!$pendingTransfer) {
            return response()->json(['message' => 'Transfer not found.'], 404);
        }

        if ($pendingTransfer->sender_user_id !== $request->user()->id) {
            abort(403);
        }

        if (!$pendingTransfer->canBeCancelled()) {
            return response()->json(['message' => 'This transfer can no longer be cancelled.'], 422);
        }

        try {
            $transactionService->releaseHold($pendingTransfer->senderAccount, $pendingTransfer->amount);

            $pendingTransfer->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

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

            return response()->json([
                'message' => 'Transfer cancelled. Funds have been released.',
                'pending_transfer' => $pendingTransfer->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to cancel transfer: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Convert currency between own USD/IQD accounts.
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
            return response()->json(['message' => 'You need both USD and IQD accounts.'], 422);
        }

        $amount = $validated['amount'];

        if ($fromAccount->available_balance < $amount) {
            $currency = \App\Models\Currency::where('code', $fromCurrency)->first();
            return response()->json([
                'message' => 'Insufficient balance. Available: ' . ($currency?->symbol ?? $fromCurrency) . number_format($fromAccount->available_balance, $currency?->decimal_places ?? 2),
            ], 422);
        }

        try {
            $result = $transactionService->transfer(
                $fromAccount,
                $toAccount,
                $amount,
                "Currency conversion: {$fromCurrency} to {$toCurrency}"
            );

            $convertedAmount = $result['converted_amount'] ?? $amount;
            $rate = $result['exchange_rate'] ?? ExchangeRateService::getRate();

            $fromCurrencyModel = \App\Models\Currency::where('code', $fromCurrency)->first();
            $toCurrencyModel = \App\Models\Currency::where('code', $toCurrency)->first();

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Currency Converted',
                'message' => "Converted " . ($fromCurrencyModel?->symbol ?? $fromCurrency) . number_format($amount, $fromCurrencyModel?->decimal_places ?? 2) . " to " . ($toCurrencyModel?->symbol ?? $toCurrency) . number_format($convertedAmount, $toCurrencyModel?->decimal_places ?? 2) . " @ " . number_format($rate, 2),
                'type' => 'success',
                'icon' => '🔄',
            ]);

            return response()->json([
                'message' => 'Currency converted successfully.',
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'amount' => $amount,
                'converted_amount' => $convertedAmount,
                'exchange_rate' => $rate,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Conversion failed: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Get current exchange rate info.
     */
    public function exchangeRate(Request $request)
    {
        $rateInfo = ExchangeRateService::getRateInfo();

        return response()->json([
            'exchange_rate' => $rateInfo,
        ]);
    }

    // ── Private Helpers ─────────────────────────────────────────

    /**
     * Find a pending transfer by ID, checking HQ if not found locally.
     */
    private function findPendingTransfer($id): ?PendingTransfer
    {
        $pendingTransfer = PendingTransfer::find($id);
        if (!$pendingTransfer) {
            $pendingTransfer = PendingTransfer::on(DistributedDatabaseService::getHqConnection())->find($id);
        }
        return $pendingTransfer;
    }

    /**
     * Sync PendingTransfer status changes to HQ and sender/receiver branches.
     */
    private function syncPendingTransferStatus(PendingTransfer $pendingTransfer, array $changes): void
    {
        $hqConnection = DistributedDatabaseService::getHqConnection();

        try {
            DB::connection($hqConnection)->table('pending_transfers')
                ->where('id', $pendingTransfer->id)
                ->update($changes);
        } catch (\Exception $e) {
            \Log::error("Failed to sync PendingTransfer status to HQ: " . $e->getMessage());
        }

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
     * Write the PendingTransfer record to the receiver's branch DB.
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
        $receiverBranch = DistributedDatabaseService::findUserBranchById($pendingTransfer->receiver_user_id);

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

        if ($receiverBranch) {
            try {
                $connection = DistributedDatabaseService::connectionForBranch($receiverBranch);
                DB::connection($connection)->table('notifications')->insert($notifData);
            } catch (\Exception $e) {
                \Log::error("Failed to write notification to receiver branch: " . $e->getMessage());
            }
        }

        try {
            DB::connection(DistributedDatabaseService::getHqConnection())->table('notifications')->insert($notifData);
        } catch (\Exception $e) {
            \Log::error("Failed to write notification to HQ: " . $e->getMessage());
        }
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
                \Log::warning("Failed to expire transfer {$transfer->reference_number}: {$e->getMessage()}");
            }
        }
    }
}

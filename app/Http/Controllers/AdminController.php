<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Loan;
use App\Models\Card;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\AuditLog;
use App\Models\KycDocument;
use App\Models\BankSetting;
use App\Models\Notification;
use App\Models\PinChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function __construct()
    {
        // Admin authorization will be handled by middleware
    }

    public function dashboard()
    {
        $reserveHealth = BankSetting::reserveHealth();

        $stats = [
            'total_users' => User::customers()->count(),
            'active_users' => User::customers()->active()->count(),
            'new_users_week' => User::customers()->where('created_at', '>=', now()->subWeek())->count(),
            'total_accounts' => Account::count(),
            'total_balance' => Account::active()->sum('balance'),
            'total_cards' => Card::count(),
            'active_cards' => Card::where('status', 'active')->count(),
            'transactions_today' => Transaction::whereDate('created_at', today())->count(),
            'transactions_week' => Transaction::where('created_at', '>=', now()->subWeek())->count(),
            'volume_today' => Transaction::whereDate('created_at', today())->sum('amount'),
            'pending_loans' => Loan::pending()->count(),
            'active_loans' => Loan::where('status', 'approved')->count(),
            'total_loan_outstanding' => Loan::where('status', 'approved')->sum('remaining_balance'),
            'open_tickets' => SupportTicket::open()->count(),
            'pending_kyc' => KycDocument::pending()->count(),
            'pending_pin_requests' => PinChangeRequest::pending()->count(),
        ];

        $recentTransactions = Transaction::with('account.user')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $recentAuditLogs = auth()->user()->isSuperAdmin()
            ? AuditLog::with('user')->orderBy('created_at', 'desc')->limit(10)->get()
            : collect();

        $recentUsers = User::customers()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $pendingLoans = Loan::pending()->with('user')->orderBy('applied_at')->limit(5)->get();

        // IQD exchange rate and loan capacity
        $iqdRate = \App\Models\Currency::where('code', 'IQD')->value('exchange_rate') ?? 1310;
        $totalLoanOutstanding = Loan::whereIn('status', ['approved', 'disbursed'])->sum('amount');
        $loanCapacity = max(0, $reserveHealth['total_deposits'] - $reserveHealth['minimum'] - $totalLoanOutstanding);

        return view('admin.dashboard', compact('stats', 'recentTransactions', 'recentAuditLogs', 'reserveHealth', 'recentUsers', 'pendingLoans', 'iqdRate', 'loanCapacity'));
    }

    public function users(Request $request)
    {
        $query = User::customers()->withCount(['accounts', 'loans', 'cards'])->with('accounts');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('national_id', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function showUser(User $user)
    {
        if (!auth()->user()->isSuperAdmin() && $user->isAdmin()) {
            abort(403, 'Unauthorized. Regular admins cannot manage or view other admin profiles.');
        }

        $user->load(['accounts', 'loans', 'kycDocuments', 'supportTickets', 'cards']);
        $recentTransactions = \App\Models\Transaction::whereIn('account_id', $user->accounts->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        return view('admin.user-detail', compact('user', 'recentTransactions'));
    }

    public function updateUserStatus(Request $request, User $user)
    {
        if (!auth()->user()->isSuperAdmin() && $user->isAdmin()) {
            abort(403, 'Unauthorized. Regular admins cannot modify other admin profiles.');
        }

        $validated = $request->validate([
            'status' => 'required|in:active,inactive,suspended,frozen,pending_verification',
        ]);

        $old = $user->status;
        $user->update($validated);

        // Notify user of status change (except for initial activation which has its own notification)
        if (!in_array($validated['status'], ['active', 'pending_verification']) || ($validated['status'] === 'active' && $old !== 'pending_verification')) {
            $statusMessages = [
                'active' => 'Your account has been reactivated. All features are now available.',
                'inactive' => 'Your account has been deactivated. Please contact support for assistance.',
                'suspended' => 'Your account has been suspended. Please contact support for more information.',
                'frozen' => 'Your account has been frozen for security reasons. Please contact support.',
            ];
            if (isset($statusMessages[$validated['status']]) && $validated['status'] !== 'active') {
                Notification::notifyUserOnBranch($user->id, [
                    'user_id' => $user->id,
                    'title' => 'Account Status Changed',
                    'message' => $statusMessages[$validated['status']],
                    'type' => $validated['status'] === 'suspended' || $validated['status'] === 'frozen' ? 'danger' : 'warning',
                    'icon' => $validated['status'] === 'frozen' ? '🔒' : '⚠️',
                    'is_read' => false,
                ]);
            }
        }

        // If activating a user, also activate their inactive cards that were pending KYC
        if ($validated['status'] === 'active' && $old === 'pending_verification') {
            $inactiveCards = $user->cards()->where('status', 'inactive')->whereNull('activated_at')->get();
            foreach ($inactiveCards as $card) {
                $card->update(['status' => 'active', 'activated_at' => now()]);
                Notification::cardActivated($card);
            }

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Account Verified! 🎉',
                'message' => 'Your identity has been verified and your account is now fully active. All features are unlocked.',
                'type' => 'success',
                'icon' => '✅',
                'action_url' => route('dashboard'),
            ]);
        }

        AuditLog::log('admin_user_status_change', [
            'model_type' => 'User',
            'model_id' => $user->id,
            'old_values' => ['status' => $old],
            'new_values' => ['status' => $validated['status']],
            'severity' => 'high',
        ]);

        return back()->with('success', "User status changed to {$validated['status']}.");
    }

    public function pendingLoans()
    {
        $loans = Loan::pending()->with(['user', 'account'])->orderBy('applied_at')->get();
        $reserveHealth = BankSetting::reserveHealth();
        return view('admin.loans', compact('loans', 'reserveHealth'));
    }

    public function approveLoan(Loan $loan)
    {
        // Check reserves before approving (HQ global check)
        $reserveHealth = BankSetting::reserveHealth();
        $projectedReserves = $reserveHealth['total_deposits'] - $loan->amount;

        if ($projectedReserves < $reserveHealth['minimum']) {
            return back()->with('error', "Cannot approve: this loan of \${$loan->amount} would drop reserves below the minimum (\${$reserveHealth['minimum']}).");
        }

        $user = $loan->user;
        $branch = $user->branch;

        if ($branch) {
            $branchConnection = \App\Services\DistributedDatabaseService::connectionForBranch($branch);
            \App\Services\DistributedDatabaseService::setActiveBranch($branch);
            
            // Sync current admin user to branch DB to prevent foreign key errors
            $this->ensureAdminExistsOnBranch($branchConnection);

            $branchLoan = Loan::find($loan->id);
            $branchAccount = Account::find($loan->account_id);
        } else {
            $branchLoan = $loan;
            $branchAccount = $loan->account;
        }

        $branchLoan->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'disbursed_at' => now(),
        ]);

        // Disburse funds to the account
        $balanceBefore = $branchAccount->balance;
        $branchAccount->balance += $loan->amount;
        $branchAccount->available_balance += $loan->amount;
        $branchAccount->save();

        Transaction::create([
            'account_id' => $branchAccount->id,
            'reference_number' => Transaction::generateReference(),
            'type' => 'deposit',
            'amount' => $loan->amount,
            'currency' => $branchAccount->currency,
            'balance_before' => $balanceBefore,
            'balance_after' => $branchAccount->balance,
            'status' => 'completed',
            'description' => "Loan Disbursement - {$loan->loan_number}",
            'channel' => 'system',
            'completed_at' => now(),
        ]);

        Notification::create([
            'user_id' => $loan->user_id,
            'title' => 'Loan Approved! 🎉',
            'message' => "Your {$loan->loan_type} loan of \${$loan->amount} has been approved!",
            'type' => 'success',
            'icon' => '✅',
            'action_url' => route('loans.show', $loan),
        ]);

        AuditLog::log('loan_approved', [
            'model_type' => 'Loan',
            'model_id' => $loan->id,
            'severity' => 'high',
        ]);

        if ($branch) {
            // Re-route admin back to HQ database connection
            \App\Services\DistributedDatabaseService::setHQ();
        }

        return back()->with('success', 'Loan approved.');
    }

    public function rejectLoan(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $user = $loan->user;
        $branch = $user->branch;

        if ($branch) {
            $branchConnection = \App\Services\DistributedDatabaseService::connectionForBranch($branch);
            \App\Services\DistributedDatabaseService::setActiveBranch($branch);
            
            // Sync current admin user to branch DB to prevent foreign key errors
            $this->ensureAdminExistsOnBranch($branchConnection);

            $branchLoan = Loan::find($loan->id);
        } else {
            $branchLoan = $loan;
        }

        $branchLoan->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        Notification::create([
            'user_id' => $loan->user_id,
            'title' => 'Loan Application Rejected',
            'message' => "Your {$loan->loan_type} loan application has been rejected. Reason: {$validated['rejection_reason']}",
            'type' => 'danger',
            'icon' => '❌',
            'action_url' => route('loans.show', $loan),
        ]);

        if ($branch) {
            // Re-route admin back to HQ database connection
            \App\Services\DistributedDatabaseService::setHQ();
        }

        return back()->with('success', 'Loan rejected.');
    }

    public function kycDocuments()
    {
        $allDocuments = collect();

        // Query HQ first (has most synced data)
        try {
            $hqDocs = \App\Models\KycDocument::on(\App\Services\DistributedDatabaseService::getHqConnection())
                ->pending()
                ->with('user')
                ->get();
            $allDocuments = $allDocuments->merge($hqDocs);
        } catch (\Exception $e) {
            \Log::warning('Failed to query HQ for KYC documents: ' . $e->getMessage());
        }

        // Also query each branch for any unsynced documents
        foreach (\App\Services\DistributedDatabaseService::branchConnections() as $branchConn) {
            try {
                $branchDocs = \App\Models\KycDocument::on($branchConn)
                    ->pending()
                    ->with('user')
                    ->get();
                foreach ($branchDocs as $doc) {
                    if (!$allDocuments->contains('id', $doc->id)) {
                        $allDocuments->push($doc);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to query branch {$branchConn} for KYC documents: " . $e->getMessage());
            }
        }

        $documents = $allDocuments->sortBy('created_at')->values();

        return view('admin.kyc', compact('documents'));
    }

    public function verifyKyc(KycDocument $document)
    {
        $user = $document->user;
        $branch = $user->branch;

        if ($branch) {
            $branchConnection = \App\Services\DistributedDatabaseService::connectionForBranch($branch);
            \App\Services\DistributedDatabaseService::setActiveBranch($branch);
            
            // Sync current admin user to branch DB to prevent foreign key errors
            $this->ensureAdminExistsOnBranch($branchConnection);

            // Re-fetch document and user on the active branch connection
            $branchDocument = KycDocument::find($document->id);
            if (!$branchDocument) {
                // Recreate if somehow missing on branch but present on HQ
                $branchDocument = KycDocument::create([
                    'id' => $document->id,
                    'user_id' => $document->user_id,
                    'document_type' => $document->document_type,
                    'document_number' => $document->document_number,
                    'file_path' => $document->file_path,
                    'file_name' => $document->file_name,
                    'mime_type' => $document->mime_type,
                    'file_size' => $document->file_size,
                    'status' => 'pending',
                    'expiry_date' => $document->expiry_date,
                ]);
            }
            
            $branchDocument->update([
                'status' => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
            $branchUser = User::find($user->id);
        } else {
            // No branch or fallback connection — update on HQ
            $document->update([
                'status' => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
            $branchUser = $user;
        }

        // Log audit trail
        AuditLog::log('kyc_document_verified', [
            'model_type' => 'KycDocument',
            'model_id' => $document->id,
            'severity' => 'medium',
            'new_values' => [
                'user_id' => $user->id,
                'type' => $document->document_type,
                'status' => 'verified',
            ],
        ]);

        // Check if user now has both passport and national_id verified
        $hasPassport = $branchUser->kycDocuments()->where('document_type', 'passport')->where('status', 'verified')->exists();
        $hasNationalId = $branchUser->kycDocuments()->where('document_type', 'national_id')->where('status', 'verified')->exists();

        if ($hasPassport && $hasNationalId) {
            // Auto-activate user
            if ($branchUser->status === 'pending_verification') {
                $branchUser->update(['status' => 'active']);

                Notification::create([
                    'user_id' => $branchUser->id,
                    'title' => 'Account Fully Verified! 🎉',
                    'message' => 'Both your Passport and National ID have been verified. Your account is now fully active!',
                    'type' => 'success',
                    'icon' => '✅',
                    'action_url' => route('dashboard'),
                ]);
            }

            // Activate any pending cards
            $pendingCards = $branchUser->cards()->where('status', 'inactive')->whereNull('activated_at')->get();
            foreach ($pendingCards as $card) {
                $card->update(['status' => 'active', 'activated_at' => now()]);
                Notification::cardActivated($card);
            }
        } else {
            Notification::create([
                'user_id' => $branchUser->id,
                'title' => 'Document Verified ✅',
                'message' => "Your {$document->document_type_label} has been verified." .
                    (!$hasPassport ? ' Please also upload your Passport.' : '') .
                    (!$hasNationalId ? ' Please also upload your National ID.' : ''),
                'type' => 'success',
                'icon' => '📄',
                'action_url' => route('profile.kyc'),
            ]);
        }

        // Re-route admin back to HQ database connection
        \App\Services\DistributedDatabaseService::setHQ();

        return back()->with('success', 'KYC document verified.');
    }

    public function rejectKyc(Request $request, KycDocument $document)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $user = $document->user;
        $branch = $user->branch;

        if ($branch) {
            $branchConnection = \App\Services\DistributedDatabaseService::connectionForBranch($branch);
            \App\Services\DistributedDatabaseService::setActiveBranch($branch);
            
            // Sync current admin user to branch DB to prevent foreign key errors
            $this->ensureAdminExistsOnBranch($branchConnection);
            
            // Re-fetch document on the active branch connection
            $branchDocument = KycDocument::find($document->id);
            if (!$branchDocument) {
                $branchDocument = KycDocument::create([
                    'id' => $document->id,
                    'user_id' => $document->user_id,
                    'document_type' => $document->document_type,
                    'document_number' => $document->document_number,
                    'file_path' => $document->file_path,
                    'file_name' => $document->file_name,
                    'mime_type' => $document->mime_type,
                    'file_size' => $document->file_size,
                    'status' => 'pending',
                    'expiry_date' => $document->expiry_date,
                ]);
            }
            
            $branchDocument->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
            ]);
        } else {
            // No branch or fallback connection — update on HQ
            $document->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
            ]);
        }

        // Log audit trail
        AuditLog::log('kyc_document_rejected', [
            'model_type' => 'KycDocument',
            'model_id' => $document->id,
            'severity' => 'medium',
            'new_values' => [
                'user_id' => $user->id,
                'type' => $document->document_type,
                'status' => 'rejected',
                'reason' => $validated['rejection_reason'],
            ],
        ]);

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Document Rejected',
            'message' => "Your {$document->document_type_label} was rejected: {$validated['rejection_reason']}. Please re-upload.",
            'type' => 'danger',
            'icon' => '❌',
            'action_url' => route('profile.kyc'),
        ]);

        // Re-route admin back to HQ database connection
        \App\Services\DistributedDatabaseService::setHQ();

        return back()->with('success', 'KYC document rejected.');
    }

    // ── Bank Settings ──────────────────────────────────────────

    public function settings()
    {
        $reserveHealth = BankSetting::reserveHealth();

        // Financial overview from HQ
        $hqConnection = \App\Services\DistributedDatabaseService::getHqConnection();

        $totalHeldFunds = DB::connection($hqConnection)->table('accounts')
            ->where('status', 'active')->whereNull('deleted_at')
            ->sum('hold_amount');

        $totalAvailableBalance = DB::connection($hqConnection)->table('accounts')
            ->where('status', 'active')->whereNull('deleted_at')
            ->sum('available_balance');

        $totalAccounts = DB::connection($hqConnection)->table('accounts')
            ->whereNull('deleted_at')->count();

        $activeAccounts = DB::connection($hqConnection)->table('accounts')
            ->where('status', 'active')->whereNull('deleted_at')->count();

        $totalCards = DB::connection($hqConnection)->table('cards')->whereNull('deleted_at')->count();
        $activeCards = DB::connection($hqConnection)->table('cards')->where('status', 'active')->count();
        $frozenCards = DB::connection($hqConnection)->table('cards')->where('status', 'frozen')->count();

        $pendingTransfers = DB::connection($hqConnection)->table('pending_transfers')
            ->where('status', 'pending')->where('expires_at', '>', now())->count();

        $totalLoans = DB::connection($hqConnection)->table('loans')->whereNull('deleted_at')->count();
        $pendingLoans = DB::connection($hqConnection)->table('loans')->where('status', 'pending')->count();
        $totalLoanAmount = DB::connection($hqConnection)->table('loans')
            ->whereIn('status', ['approved', 'disbursed'])->whereNull('deleted_at')
            ->sum('amount');

        $totalUsers = DB::connection($hqConnection)->table('users')->whereNull('deleted_at')->count();

        $financialOverview = compact(
            'totalHeldFunds', 'totalAvailableBalance', 'totalAccounts', 'activeAccounts',
            'totalCards', 'activeCards', 'frozenCards', 'pendingTransfers',
            'totalLoans', 'pendingLoans', 'totalLoanAmount', 'totalUsers'
        );

        $settings = [
            'loan_reserve_minimum' => BankSetting::get('loan_reserve_minimum', 50000),
            'transfer_expiry_hours' => BankSetting::get('transfer_expiry_hours', 48),
            'max_pin_attempts' => BankSetting::get('max_pin_attempts', 3),
            'bank_name' => BankSetting::get('bank_name', 'Distributed Bank'),
            'support_email' => BankSetting::get('support_email', 'support@nexusbank.com'),
            'default_currency' => BankSetting::get('default_currency', 'USD'),
            'daily_transfer_limit' => BankSetting::get('daily_transfer_limit', 10000),
            'min_transfer_amount' => BankSetting::get('min_transfer_amount', 1),
            'max_transfer_amount' => BankSetting::get('max_transfer_amount', 50000),
        ];

        // Loan capacity: how much can be lent before hitting reserve minimum
        $loanCapacity = max(0, $reserveHealth['total_deposits'] - $reserveHealth['minimum'] - $financialOverview['totalLoanAmount']);

        // IQD exchange rate for dual-currency display
        $iqdRate = \App\Models\Currency::where('code', 'IQD')->value('exchange_rate') ?? 1310;

        $isSuperAdmin = auth()->user()->isSuperAdmin();

        return view('admin.settings', compact('reserveHealth', 'settings', 'financialOverview', 'loanCapacity', 'iqdRate', 'isSuperAdmin'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'loan_reserve_minimum' => 'required|numeric|min:0',
            'transfer_expiry_hours' => 'required|integer|min:1|max:168',
            'max_pin_attempts' => 'required|integer|min:1|max:10',
            'bank_name' => 'required|string|max:100',
            'support_email' => 'required|email|max:255',
            'default_currency' => 'required|string|size:3',
            'daily_transfer_limit' => 'required|numeric|min:0',
            'min_transfer_amount' => 'required|numeric|min:0',
            'max_transfer_amount' => 'required|numeric|min:0',
        ]);

        foreach ($validated as $key => $value) {
            BankSetting::set($key, $value);
        }

        AuditLog::log('admin_settings_updated', [
            'new_values' => $validated,
            'severity' => 'high',
        ]);

        return back()->with('success', 'Bank settings updated successfully.');
    }

    // ── PIN Change Requests ─────────────────────────────────────

    public function pinRequests()
    {
        $pendingRequests = PinChangeRequest::pending()
            ->with(['user', 'card'])
            ->orderBy('created_at')
            ->get();

        $processedRequests = PinChangeRequest::processed()
            ->with(['user', 'card', 'processedBy'])
            ->orderBy('processed_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.pin-requests', compact('pendingRequests', 'processedRequests'));
    }

    public function approvePinRequest(Request $request, PinChangeRequest $pinRequest)
    {
        if (!$pinRequest->isPending()) {
            return back()->with('error', 'This request has already been processed.');
        }

        $user = $pinRequest->user;
        $branch = $user->branch;

        if ($branch) {
            $branchConnection = \App\Services\DistributedDatabaseService::connectionForBranch($branch);
            \App\Services\DistributedDatabaseService::setActiveBranch($branch);
            
            // Sync current admin user to branch DB to prevent foreign key errors
            $this->ensureAdminExistsOnBranch($branchConnection);

            $branchPinRequest = PinChangeRequest::find($pinRequest->id);
            $branchCard = Card::find($pinRequest->card_id);
        } else {
            $branchPinRequest = $pinRequest;
            $branchCard = $pinRequest->card;
        }

        // Generate new PIN
        $pinData = Card::generatePin();
        $branchCard->changePin($pinData['plain']);

        // If card was frozen due to stolen PIN, unfreeze it
        if ($branchCard->status === 'frozen') {
            $branchCard->update(['status' => 'active', 'pin_attempts' => 0]);
        }

        $branchPinRequest->update([
            'status' => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'admin_note' => $request->input('admin_note'),
        ]);

        // Notify user with the new PIN (sent as a secure notification)
        Notification::create([
            'user_id' => $pinRequest->user_id,
            'title' => 'PIN Changed Successfully! 🔐',
            'message' => "Your PIN change request for card ending in {$branchCard->card_number_last4} has been approved. Your new PIN is: {$pinData['plain']}. Please memorize it and do not share it with anyone.",
            'type' => 'security',
            'icon' => '🔐',
            'action_url' => route('cards.index'),
            'data' => ['action' => 'pin_changed', 'card_id' => $branchCard->id],
        ]);

        AuditLog::log('admin_pin_change_approved', [
            'model_type' => 'PinChangeRequest',
            'model_id' => $pinRequest->id,
            'severity' => 'high',
            'new_values' => [
                'card_last4' => $branchCard->card_number_last4,
                'user' => $pinRequest->user->name,
            ],
        ]);

        if ($branch) {
            // Re-route admin back to HQ database connection
            \App\Services\DistributedDatabaseService::setHQ();
        }

        return back()->with('success', "PIN changed for card ending in {$branchCard->card_number_last4}. User has been notified.");
    }

    public function rejectPinRequest(Request $request, PinChangeRequest $pinRequest)
    {
        if (!$pinRequest->isPending()) {
            return back()->with('error', 'This request has already been processed.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $user = $pinRequest->user;
        $branch = $user->branch;

        if ($branch) {
            $branchConnection = \App\Services\DistributedDatabaseService::connectionForBranch($branch);
            \App\Services\DistributedDatabaseService::setActiveBranch($branch);
            
            // Sync current admin user to branch DB to prevent foreign key errors
            $this->ensureAdminExistsOnBranch($branchConnection);

            $branchPinRequest = PinChangeRequest::find($pinRequest->id);
            $branchCard = Card::find($pinRequest->card_id);
        } else {
            $branchPinRequest = $pinRequest;
            $branchCard = $pinRequest->card;
        }

        $branchPinRequest->update([
            'status' => 'rejected',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'admin_note' => $validated['rejection_reason'],
        ]);

        Notification::create([
            'user_id' => $pinRequest->user_id,
            'title' => 'PIN Change Request Denied',
            'message' => "Your PIN change request for card ending in {$branchCard->card_number_last4} was rejected. Reason: {$validated['rejection_reason']}",
            'type' => 'danger',
            'icon' => '❌',
            'action_url' => route('cards.index'),
        ]);

        if ($branch) {
            // Re-route admin back to HQ database connection
            \App\Services\DistributedDatabaseService::setHQ();
        }

        return back()->with('success', 'PIN change request rejected.');
    }

    /**
     * Show global audit logs (action history viewer) for Super Admins.
     */
    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('severity', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        // Get unique actions for filter dropdown
        $availableActions = AuditLog::select('action')->distinct()->pluck('action')->toArray();

        return view('admin.audit-logs', compact('logs', 'availableActions'));
    }

    // ── Support Tickets ────────────────────────────────────────

    /**
     * List all support tickets with filters.
     */
    public function supportTickets(Request $request)
    {
        $query = SupportTicket::with(['user', 'replies', 'assignee'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $tickets = $query->paginate(20)->withQueryString();

        $stats = [
            'total'       => SupportTicket::count(),
            'open'        => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved'    => SupportTicket::where('status', 'resolved')->count(),
            'closed'      => SupportTicket::where('status', 'closed')->count(),
        ];

        return view('admin.support.index', compact('tickets', 'stats'));
    }

    /**
     * Show a support ticket detail with replies.
     */
    public function showSupportTicket(SupportTicket $ticket)
    {
        $ticket->load(['user', 'replies.user', 'assignee']);

        // Get all staff for assignment dropdown
        $staff = User::whereIn('role', ['admin', 'super_admin'])->orderBy('name')->get();

        return view('admin.support.show', compact('ticket', 'staff'));
    }

    /**
     * Reply to a support ticket as staff.
     */
    public function replySupportTicket(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message'     => 'required|string|max:5000',
            'attachment'  => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt',
            'is_internal' => 'nullable|boolean',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support', 'public');
        }

        $isInternal = $request->boolean('is_internal');

        SupportTicketReply::create([
            'ticket_id'       => $ticket->id,
            'user_id'         => auth()->id(),
            'message'         => $validated['message'],
            'is_staff_reply'  => true,
            'is_internal'     => $isInternal,
            'attachment_path' => $attachmentPath,
        ]);

        // Update ticket status to awaiting_response if currently open (skip for internal notes)
        if (!$isInternal && in_array($ticket->status, ['open', 'in_progress'])) {
            $ticket->update([
                'status'      => 'awaiting_response',
                'assigned_to' => $ticket->assigned_to ?? auth()->id(),
            ]);
        }

        // Notify the ticket owner (skip for internal notes)
        if (!$isInternal) {
            Notification::create([
                'user_id'    => $ticket->user_id,
                'title'      => 'Staff Reply on Ticket 💬',
                'message'    => "Staff responded to your ticket {$ticket->ticket_number}: {$ticket->subject}",
                'type'       => 'info',
                'icon'       => '💬',
                'action_url' => route('support.show', $ticket),
            ]);
        }

        return back()->with('success', $isInternal ? 'Internal note added.' : 'Reply sent to customer.');
    }

    /**
     * Update a support ticket's status.
     */
    public function updateTicketStatus(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,awaiting_response,resolved,closed',
        ]);

        $oldStatus = $ticket->status;
        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'resolved' && !$ticket->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $ticket->update($updateData);

        // Notify the ticket owner about status change
        Notification::create([
            'user_id'    => $ticket->user_id,
            'title'      => 'Ticket Status Updated',
            'message'    => "Your ticket {$ticket->ticket_number} status changed from " . ucwords(str_replace('_', ' ', $oldStatus)) . " to " . ucwords(str_replace('_', ' ', $validated['status'])) . ".",
            'type'       => $validated['status'] === 'resolved' ? 'success' : 'info',
            'icon'       => $validated['status'] === 'resolved' ? '✅' : '🔄',
            'action_url' => route('support.show', $ticket),
        ]);

        AuditLog::log('ticket_status_updated', [
            'model_type' => 'SupportTicket',
            'model_id'   => $ticket->id,
            'old_values'  => ['status' => $oldStatus],
            'new_values'  => ['status' => $validated['status']],
        ]);

        return back()->with('success', "Ticket status updated to {$validated['status']}.");
    }

    /**
     * Assign a ticket to a staff member.
     */
    public function assignTicket(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $assignee = User::findOrFail($validated['assigned_to']);

        $ticket->update(['assigned_to' => $assignee->id]);

        Notification::create([
            'user_id'    => $ticket->user_id,
            'title'      => 'Ticket Assigned',
            'message'    => "Your ticket {$ticket->ticket_number} has been assigned to {$assignee->name}.",
            'type'       => 'info',
            'icon'       => '👤',
            'action_url' => route('support.show', $ticket),
        ]);

        AuditLog::log('ticket_assigned', [
            'model_type'  => 'SupportTicket',
            'model_id'    => $ticket->id,
            'new_values'  => ['assigned_to' => $assignee->name],
        ]);

        return back()->with('success', "Ticket assigned to {$assignee->name}.");
    }

    /**
     * Preview secure KYC document files.
     */
    public function kycFile(KycDocument $document)
    {
        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }
        return response()->file(storage_path('app/' . $document->file_path));
    }

    /**
     * Ensure the currently logged-in admin user exists in a branch DB to avoid foreign key errors.
     */
    private function ensureAdminExistsOnBranch(string $branchConnection): void
    {
        $adminId = auth()->id();
        if (!$adminId) return;

        $adminOnHq = DB::connection(\App\Services\DistributedDatabaseService::getHqConnection())
            ->table('users')
            ->find($adminId);

        if ($adminOnHq) {
            DB::connection($branchConnection)->table('users')->insertOrIgnore((array) $adminOnHq);
        }
    }
}

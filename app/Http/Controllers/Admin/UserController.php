<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Models\Notification;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
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

    public function show(User $user)
    {
        if (!auth()->user()->isSuperAdmin() && $user->isAdmin()) {
            abort(403, 'Unauthorized. Regular admins cannot manage or view other admin profiles.');
        }

        $user->load(['accounts', 'loans', 'kycDocuments', 'supportTickets', 'cards']);
        $recentTransactions = Transaction::whereIn('account_id', $user->accounts->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.user-detail', compact('user', 'recentTransactions'));
    }

    public function updateStatus(Request $request, User $user)
    {
        if (!auth()->user()->isSuperAdmin() && $user->isAdmin()) {
            abort(403, 'Unauthorized. Regular admins cannot modify other admin profiles.');
        }

        $validated = $request->validate([
            'status' => 'required|in:active,inactive,suspended,frozen,pending_verification',
        ]);

        $old = $user->status;
        $user->update($validated);

        // Notify user of status change
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
}

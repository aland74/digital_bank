<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\BankSetting;
use App\Models\Notification;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        $loans = Loan::pending()->with(['user', 'account'])->orderBy('applied_at')->get();
        $reserveHealth = BankSetting::reserveHealth();

        return view('admin.loans', compact('loans', 'reserveHealth'));
    }

    public function approve(Loan $loan)
    {
        // Check reserves before approving
        $reserveHealth = BankSetting::reserveHealth();
        $projectedReserves = $reserveHealth['total_deposits'] - $loan->amount;

        if ($projectedReserves < $reserveHealth['minimum']) {
            return back()->with('error', "Cannot approve: this loan of \${$loan->amount} would drop reserves below the minimum (\${$reserveHealth['minimum']}).");
        }

        $loan->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'disbursed_at' => now(),
        ]);

        // Disburse funds to the account
        $account = $loan->account;
        $balanceBefore = $account->balance;

        $account->balance += $loan->amount;
        $account->available_balance += $loan->amount;
        $account->save();

        Transaction::create([
            'account_id' => $account->id,
            'reference_number' => Transaction::generateReference(),
            'type' => 'deposit',
            'amount' => $loan->amount,
            'currency' => $account->currency,
            'balance_before' => $balanceBefore,
            'balance_after' => $account->balance,
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

        return back()->with('success', 'Loan approved.');
    }

    public function reject(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $loan->update([
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

        return back()->with('success', 'Loan rejected.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\BankSetting;
use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $loans = $request->user()->loans()->with('account')->orderBy('created_at', 'desc')->get();
        return view('loans.index', compact('loans'));
    }

    public function show(Loan $loan)
    {
        $this->authorize('view', $loan);
        $repayments = $loan->repayments()->orderBy('installment_number')->get();

        return view('loans.show', compact('loan', 'repayments'));
    }

    public function apply()
    {
        $cards = auth()->user()->cards()->active()->with('account')->get();

        if ($cards->isEmpty()) {
            return redirect()->route('loans.index')->with('error', 'You must have an active card to apply for a loan.');
        }

        // Check if reserves are healthy — show warning if not
        $reserveHealth = BankSetting::reserveHealth();

        return view('loans.apply', compact('cards', 'reserveHealth'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'loan_type' => 'required|in:personal,home,auto,business,education',
            'amount' => 'required|numeric|min:1000|max:1000000',
            'term_months' => 'required|integer|min:6|max:360',
            'card_id' => 'required|exists:cards,id',
            'purpose' => 'nullable|string|max:500',
        ]);

        $card = $request->user()->cards()->findOrFail($validated['card_id']);

        // ── Bank Reserve Check ─────────────────────────────────
        $reserveHealth = BankSetting::reserveHealth();

        if (!$reserveHealth['healthy']) {
            // Auto-reject — bank doesn't have sufficient reserves
            Notification::loanRejectedReserves($request->user()->id, $validated['amount']);

            return redirect()->route('loans.index')
                ->with('error', 'We are unable to process loan applications at this time. Our lending capacity has been temporarily reached. Please try again later.');
        }

        // Check if approving this loan would drop reserves below minimum
        $projectedReserves = $reserveHealth['total_deposits'] - $validated['amount'];
        if ($projectedReserves < $reserveHealth['minimum']) {
            Notification::loanRejectedReserves($request->user()->id, $validated['amount']);

            return redirect()->route('loans.index')
                ->with('error', 'The requested loan amount exceeds our current lending capacity. Please try a smaller amount or apply later.');
        }

        $interestRate = $this->getInterestRate($validated['loan_type']);
        $monthlyPayment = $this->calculateMonthlyPayment($validated['amount'], $interestRate, $validated['term_months']);
        $totalInterest = ($monthlyPayment * $validated['term_months']) - $validated['amount'];

        $loan = $request->user()->loans()->create([
            'account_id' => $card->account_id,
            'loan_number' => Loan::generateLoanNumber(),
            'loan_type' => $validated['loan_type'],
            'amount' => $validated['amount'],
            'interest_rate' => $interestRate,
            'term_months' => $validated['term_months'],
            'monthly_payment' => $monthlyPayment,
            'total_interest' => $totalInterest,
            'remaining_balance' => $validated['amount'] + $totalInterest,
            'status' => 'pending',
            'purpose' => $validated['purpose'],
            'applied_at' => now(),
        ]);

        // Notify user
        Notification::create([
            'user_id' => $request->user()->id,
            'title' => 'Loan Application Submitted',
            'message' => "Your {$validated['loan_type']} loan application for \${$validated['amount']} has been submitted and is under review.",
            'type' => 'info',
            'icon' => '📋',
            'action_url' => route('loans.show', $loan),
        ]);

        // Notify admins
        $admins = \App\Models\User::admins()->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'New Loan Application',
                'message' => "{$request->user()->name} applied for a {$validated['loan_type']} loan of \${$validated['amount']}.",
                'type' => 'info',
                'icon' => '📋',
                'action_url' => route('admin.loans'),
            ]);
        }

        return redirect()->route('loans.show', $loan)
            ->with('success', 'Loan application submitted successfully. We will review it shortly.');
    }

    private function getInterestRate(string $type): float
    {
        return match($type) {
            'personal' => 8.50,
            'home' => 4.25,
            'auto' => 5.75,
            'business' => 7.00,
            'education' => 3.50,
            default => 8.50,
        };
    }

    private function calculateMonthlyPayment(float $principal, float $annualRate, int $months): float
    {
        $monthlyRate = ($annualRate / 100) / 12;
        if ($monthlyRate === 0.0) {
            return $principal / $months;
        }
        return round($principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1), 2);
    }

    public function pay(Request $request, Loan $loan)
    {
        $this->authorize('update', $loan);

        if ($loan->remaining_balance <= 0) {
            return back()->with('error', 'This loan is already fully paid.');
        }

        $minAmount = min($loan->remaining_balance, $loan->monthly_payment);

        $validated = $request->validate([
            'amount' => "required|numeric|min:{$minAmount}|max:{$loan->remaining_balance}",
        ]);

        $amount = $validated['amount'];

        try {
        DB::transaction(function () use ($loan, $amount) {
            // Lock the account row to prevent concurrent modifications
            $account = \App\Models\Account::lockForUpdate()->findOrFail($loan->account_id);

            if ($account->balance < $amount) {
                throw new \RuntimeException('Insufficient balance in the linked account to make this payment.');
            }

            $balanceBefore = $account->balance;

            // Deduct from account
            $account->decrement('balance', $amount);
            $account->decrement('available_balance', $amount);

            // Update loan
            $loan->decrement('remaining_balance', $amount);
            $loan->increment('total_paid', $amount);

            if ($loan->remaining_balance <= 0) {
                $loan->update(['status' => 'completed']);
            }

            // Record transaction
            Transaction::create([
                'account_id' => $account->id,
                'reference_number' => Transaction::generateReference(),
                'type' => 'payment',
                'amount' => $amount,
                'currency' => $account->currency,
                'balance_before' => $balanceBefore,
                'balance_after' => $account->fresh()->balance,
                'status' => 'completed',
                'description' => "Loan Repayment - {$loan->loan_number}",
                'channel' => 'web',
                'completed_at' => now(),
            ]);
        });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Payment of $" . number_format($amount, 2) . " successful!");
    }
}

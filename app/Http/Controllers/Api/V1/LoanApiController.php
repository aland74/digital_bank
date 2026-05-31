<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BankSetting;
use App\Models\Loan;
use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanApiController extends Controller
{
    /**
     * List user's loans with account info.
     */
    public function index(Request $request)
    {
        $loans = $request->user()->loans()->with('account:id,account_number,currency')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'loans' => $loans->map(fn($loan) => [
                'id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'loan_type' => $loan->loan_type,
                'amount' => $loan->amount,
                'interest_rate' => $loan->interest_rate,
                'term_months' => $loan->term_months,
                'monthly_payment' => $loan->monthly_payment,
                'total_interest' => $loan->total_interest,
                'total_paid' => $loan->total_paid,
                'remaining_balance' => $loan->remaining_balance,
                'progress_percentage' => $loan->progress_percentage,
                'status' => $loan->status,
                'purpose' => $loan->purpose,
                'applied_at' => $loan->applied_at,
                'account' => $loan->account ? [
                    'account_number' => $loan->account->account_number,
                    'currency' => $loan->account->currency,
                ] : null,
            ]),
        ]);
    }

    /**
     * Apply for a loan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'loan_type' => 'required|in:personal,home,auto,business,education',
            'amount' => 'required|numeric|min:1|max:1000000000',
            'term_months' => 'required|integer|min:6|max:360',
            'card_id' => 'required|exists:cards,id',
            'purpose' => 'nullable|string|max:500',
        ]);

        $card = $request->user()->cards()->findOrFail($validated['card_id']);
        $account = $card->account;

        // Validate amount based on currency
        $currency = $account->currency ?? 'USD';
        if ($currency === 'IQD') {
            if ($validated['amount'] < 1000000 || $validated['amount'] > 1000000000) {
                return response()->json(['message' => 'IQD loan amount must be between 1,000,000 and 1,000,000,000 IQD.'], 422);
            }
        } else {
            if ($validated['amount'] < 1000 || $validated['amount'] > 1000000) {
                return response()->json(['message' => 'USD loan amount must be between $1,000 and $1,000,000.'], 422);
            }
        }

        // Bank reserve check
        $reserveHealth = BankSetting::reserveHealth();

        if (!$reserveHealth['healthy']) {
            Notification::loanRejectedReserves($request->user()->id, $validated['amount']);
            return response()->json([
                'message' => 'We are unable to process loan applications at this time. Our lending capacity has been temporarily reached. Please try again later.',
            ], 422);
        }

        // Check if approving this loan would drop reserves below minimum
        $projectedReserves = $reserveHealth['total_deposits'] - $validated['amount'];
        if ($projectedReserves < $reserveHealth['minimum']) {
            Notification::loanRejectedReserves($request->user()->id, $validated['amount']);
            return response()->json([
                'message' => 'The requested loan amount exceeds our current lending capacity. Please try a smaller amount or apply later.',
            ], 422);
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
            ]);
        }

        return response()->json([
            'message' => 'Loan application submitted successfully. We will review it shortly.',
            'loan' => $loan->fresh(),
        ], 201);
    }

    /**
     * Show a single loan with repayments.
     */
    public function show(Request $request, $id)
    {
        $loan = $request->user()->loans()->with('account:id,account_number,currency')->find($id);

        if (!$loan) {
            return response()->json(['message' => 'Loan not found.'], 404);
        }

        $repayments = $loan->repayments()->orderBy('installment_number')->get();

        return response()->json([
            'loan' => [
                'id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'loan_type' => $loan->loan_type,
                'amount' => $loan->amount,
                'interest_rate' => $loan->interest_rate,
                'term_months' => $loan->term_months,
                'monthly_payment' => $loan->monthly_payment,
                'total_interest' => $loan->total_interest,
                'total_paid' => $loan->total_paid,
                'remaining_balance' => $loan->remaining_balance,
                'progress_percentage' => $loan->progress_percentage,
                'status' => $loan->status,
                'purpose' => $loan->purpose,
                'applied_at' => $loan->applied_at,
                'approved_at' => $loan->approved_at,
                'disbursed_at' => $loan->disbursed_at,
                'maturity_date' => $loan->maturity_date,
                'account' => $loan->account ? [
                    'account_number' => $loan->account->account_number,
                    'currency' => $loan->account->currency,
                ] : null,
            ],
            'repayments' => $repayments,
        ]);
    }

    /**
     * Make a loan repayment.
     */
    public function pay(Request $request, $id)
    {
        $loan = $request->user()->loans()->find($id);

        if (!$loan) {
            return response()->json(['message' => 'Loan not found.'], 404);
        }

        if ($loan->remaining_balance <= 0) {
            return response()->json(['message' => 'This loan is already fully paid.'], 422);
        }

        $minAmount = min($loan->remaining_balance, $loan->monthly_payment);

        $validated = $request->validate([
            'amount' => "required|numeric|min:{$minAmount}|max:{$loan->remaining_balance}",
        ]);

        $amount = $validated['amount'];

        try {
            DB::transaction(function () use ($loan, $amount) {
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
                    'channel' => 'api',
                    'completed_at' => now(),
                ]);
            });

            return response()->json([
                'message' => "Payment of $" . number_format($amount, 2) . " successful!",
                'loan' => $loan->fresh(),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── Private Helpers ─────────────────────────────────────────

    private function getInterestRate(string $type): float
    {
        return match ($type) {
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
}

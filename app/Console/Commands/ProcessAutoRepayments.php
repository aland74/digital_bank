<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan;
use App\Models\Transaction;

class ProcessAutoRepayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:auto-repay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically deduct monthly loan payments from linked accounts if not paid.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find all active loans with a remaining balance
        $loans = Loan::where('status', 'active')->where('remaining_balance', '>', 0)->get();

        $processed = 0;

        foreach ($loans as $loan) {
            // In a real scenario, we'd check if the current date matches the monthly due date
            // For now, let's assume this runs on their billing cycle.
            // If they haven't made a payment for this period, deduct the monthly payment amount.
            
            $amountToDeduct = min($loan->monthly_payment, $loan->remaining_balance);
            $account = $loan->account; // The account linked to their card

            if ($account && $account->balance >= $amountToDeduct) {
                // Deduct from account
                $account->balance -= $amountToDeduct;
                $account->available_balance -= $amountToDeduct;
                $account->save();

                // Update loan
                $loan->remaining_balance -= $amountToDeduct;
                $loan->total_paid += $amountToDeduct;
                
                if ($loan->remaining_balance <= 0) {
                    $loan->status = 'completed';
                }
                $loan->save();

                // Record transaction
                Transaction::create([
                    'account_id' => $account->id,
                    'reference_number' => Transaction::generateReference(),
                    'type' => 'payment',
                    'amount' => $amountToDeduct,
                    'currency' => $account->currency,
                    'balance_before' => $account->balance + $amountToDeduct,
                    'balance_after' => $account->balance,
                    'status' => 'completed',
                    'description' => "Auto Loan Repayment - {$loan->loan_number}",
                    'channel' => 'system',
                    'completed_at' => now(),
                ]);

                $this->info("Processed auto repayment for loan: {$loan->loan_number}");
                $processed++;
            } else {
                $this->warn("Insufficient funds to auto-repay loan: {$loan->loan_number}");
                // Could trigger a failed payment notification here
            }
        }

        $this->info("Completed! Processed {$processed} auto repayments.");
    }
}

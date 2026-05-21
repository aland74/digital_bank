<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Process an internal transfer between two accounts.
     */
    public function transfer(Account $fromAccount, Account $toAccount, float $amount, string $description = '', string $channel = 'web'): array
    {
        if ($fromAccount->id === $toAccount->id) {
            throw new \InvalidArgumentException('Cannot transfer to the same account.');
        }

        if (!$fromAccount->isActive() || !$toAccount->isActive()) {
            throw new \RuntimeException('One or both accounts are not active.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive.');
        }

        if ($fromAccount->available_balance < $amount) {
            throw new \RuntimeException('Insufficient funds.');
        }

        // Cross-currency conversion (IQD <-> USD only)
        $convertedAmount = $amount;
        $exchangeRate = null;
        if ($fromAccount->currency !== $toAccount->currency) {
            $convertedAmount = ExchangeRateService::convert($amount, $fromAccount->currency, $toAccount->currency);
            $exchangeRate = ExchangeRateService::getRate();
        }

        // --- FRAUD DETECTION ---
        $fraudDetectionService = app(\App\Services\FraudDetectionService::class);
        $fraudResult = $fraudDetectionService->evaluateTransfer($fromAccount->user, $amount);

        if ($fraudResult['status'] === 'blocked') {
            throw new \RuntimeException($fraudResult['message']);
        }
        // -----------------------

        // --- DAILY/MONTHLY LIMIT CHECK ---
        $this->checkTransferLimits($fromAccount, $amount);
        // ---------------------------------

        $fromConnection = $fromAccount->getConnectionName();
        $toConnection = $toAccount->getConnectionName();

        return DB::connection($fromConnection)->transaction(function () use ($fromAccount, $toAccount, $amount, $convertedAmount, $exchangeRate, $description, $channel, $fromConnection, $toConnection) {
            // Lock accounts on their respective connections
            $fromAccount = Account::on($fromConnection)->lockForUpdate()->find($fromAccount->id);
            $toAccount = Account::on($toConnection)->lockForUpdate()->find($toAccount->id);

            // Double-check balance after lock
            if ($fromAccount->available_balance < $amount) {
                throw new \RuntimeException('Insufficient funds.');
            }

            $reference = Transaction::generateReference();
            $creditRef = Transaction::generateReference();

            // Build description with conversion info
            $debitDescription = $description ?: 'Transfer to ' . $toAccount->account_number;
            $creditDescription = $description ?: 'Transfer from ' . $fromAccount->account_number;
            if ($exchangeRate) {
                $debitDescription .= " (Converted {$amount} {$fromAccount->currency} → {$convertedAmount} {$toAccount->currency} @ {$exchangeRate})";
                $creditDescription .= " (Converted from {$amount} {$fromAccount->currency} @ {$exchangeRate})";
            }

            // Debit transaction (in sender's currency)
            $debitTxn = Transaction::create([
                'account_id' => $fromAccount->id,
                'reference_number' => $reference,
                'type' => 'transfer_out',
                'amount' => $amount,
                'currency' => $fromAccount->currency,
                'balance_before' => $fromAccount->balance,
                'balance_after' => $fromAccount->balance - $amount,
                'status' => 'completed',
                'description' => $debitDescription,
                'recipient_account_id' => $toAccount->id,
                'recipient_name' => $toAccount->user->name,
                'channel' => $channel,
                'ip_address' => request()->ip(),
                'completed_at' => now(),
            ]);

            // Ledger entry for sender (on current branch)
            \App\Models\LedgerEntry::create([
                'transaction_reference' => $reference,
                'account_id' => $fromAccount->id,
                'type' => 'debit',
                'amount' => $amount,
                'currency' => $fromAccount->currency,
            ]);

            // Update sender balance (on current branch)
            $fromAccount->update([
                'balance' => $fromAccount->balance - $amount,
                'available_balance' => $fromAccount->available_balance - $amount,
            ]);

            // ── Write credit-side data to RECEIVER's branch ──
            $receiverBranch = DistributedDatabaseService::findUserBranchById($toAccount->user_id);
            $receiverConnection = $receiverBranch
                ? DistributedDatabaseService::connectionForBranch($receiverBranch)
                : DistributedDatabaseService::getHqConnection();

            $creditTxnData = [
                'account_id' => $toAccount->id,
                'reference_number' => $creditRef,
                'type' => 'transfer_in',
                'amount' => $convertedAmount,
                'currency' => $toAccount->currency,
                'balance_before' => $toAccount->balance,
                'balance_after' => $toAccount->balance + $convertedAmount,
                'status' => 'completed',
                'description' => $creditDescription,
                'recipient_account_id' => $fromAccount->id,
                'recipient_name' => $fromAccount->user->name,
                'channel' => $channel,
                'ip_address' => request()->ip(),
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $creditLedgerData = [
                'transaction_reference' => $creditRef,
                'account_id' => $toAccount->id,
                'type' => 'credit',
                'amount' => $convertedAmount,
                'currency' => $toAccount->currency,
                'created_at' => now(),
            ];

            try {
                DB::connection($receiverConnection)->table('transactions')->insert($creditTxnData);
                DB::connection($receiverConnection)->table('ledger_entries')->insert($creditLedgerData);
                DB::connection($receiverConnection)->table('accounts')
                    ->where('id', $toAccount->id)
                    ->update([
                        'balance' => $toAccount->balance + $convertedAmount,
                        'available_balance' => $toAccount->available_balance + $convertedAmount,
                        'updated_at' => now(),
                    ]);
            } catch (\Exception $e) {
                \Log::warning("Failed to write credit transaction to receiver branch: {$e->getMessage()}");
                $hqConnection = DistributedDatabaseService::getHqConnection();
                DB::connection($hqConnection)->table('transactions')->insert($creditTxnData);
                DB::connection($hqConnection)->table('ledger_entries')->insert($creditLedgerData);
                DB::connection($hqConnection)->table('accounts')
                    ->where('id', $toAccount->id)
                    ->update([
                        'balance' => $toAccount->balance + $convertedAmount,
                        'available_balance' => $toAccount->available_balance + $convertedAmount,
                        'updated_at' => now(),
                    ]);
            }

            // Also write credit transaction to HQ for admin visibility
            try {
                $hqConnection = DistributedDatabaseService::getHqConnection();
                if ($receiverConnection !== $hqConnection) {
                    DB::connection($hqConnection)->table('transactions')->insert($creditTxnData);
                    DB::connection($hqConnection)->table('ledger_entries')->insert($creditLedgerData);
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to write credit transaction to HQ: {$e->getMessage()}");
            }

            $creditTxn = new Transaction($creditTxnData);
            $creditTxn->id = 0;

            // Dispatch Event
            event(new \App\Events\TransactionCompleted($debitTxn, 'transfer', [
                'model_type' => 'Transaction',
                'model_id' => $debitTxn->id,
                'new_values' => [
                    'from_account' => $fromAccount->account_number,
                    'to_account' => $toAccount->account_number,
                    'amount' => $amount,
                    'converted_amount' => $convertedAmount,
                    'from_currency' => $fromAccount->currency,
                    'to_currency' => $toAccount->currency,
                    'exchange_rate' => $exchangeRate,
                    'reference' => $reference,
                ],
                'severity' => $amount > 5000 ? 'high' : 'medium',
            ]));

            return [
                'debit_transaction' => $debitTxn,
                'credit_transaction' => $creditTxn,
                'reference' => $reference,
                'exchange_rate' => $exchangeRate,
                'converted_amount' => $convertedAmount,
            ];
        });
    }

    /**
     * Hold funds on an account (for pending transfers).
     * Reduces available_balance but keeps balance unchanged.
     */
    public function holdFunds(Account $account, float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Hold amount must be positive.');
        }

        $connection = $account->getConnectionName();

        DB::connection($connection)->transaction(function () use ($account, $amount, $connection) {
            $account = Account::on($connection)->lockForUpdate()->find($account->id);

            if ($account->available_balance < $amount) {
                throw new \RuntimeException('Insufficient available funds for hold.');
            }

            $account->update([
                'hold_amount' => $account->hold_amount + $amount,
                'available_balance' => $account->available_balance - $amount,
            ]);
        });
    }

    /**
     * Release held funds back to available balance.
     */
    public function releaseHold(Account $account, float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Release amount must be positive.');
        }

        $connection = $account->getConnectionName();

        DB::connection($connection)->transaction(function () use ($account, $amount, $connection) {
            $account = Account::on($connection)->lockForUpdate()->find($account->id);

            $releaseAmount = min($amount, $account->hold_amount);

            $account->update([
                'hold_amount' => max(0, $account->hold_amount - $releaseAmount),
                'available_balance' => $account->available_balance + $releaseAmount,
            ]);
        });
    }

    /**
     * Execute a transfer from held funds (when receiver accepts).
     * Moves money from hold to recipient without touching available_balance again.
     */
    public function executeHeldTransfer(Account $fromAccount, Account $toAccount, float $amount, string $description = '', ?float $lockedExchangeRate = null): array
    {
        $fromConnection = $fromAccount->getConnectionName();
        $toConnection = $toAccount->getConnectionName();

        // Use sender's connection for the main transaction (debit side)
        return DB::connection($fromConnection)->transaction(function () use ($fromAccount, $toAccount, $amount, $description, $lockedExchangeRate, $fromConnection, $toConnection) {
            $fromAccount = Account::on($fromConnection)->lockForUpdate()->find($fromAccount->id);
            $toAccount = Account::on($toConnection)->lockForUpdate()->find($toAccount->id);

            // Validate accounts are still active
            if (!$fromAccount->isActive()) {
                throw new \RuntimeException('Sender account is no longer active.');
            }
            if (!$toAccount->isActive()) {
                throw new \RuntimeException('Recipient account is no longer active.');
            }

            // Validate hold amount is sufficient
            if ($fromAccount->hold_amount < $amount) {
                throw new \RuntimeException('Insufficient held funds. The held amount may have changed.');
            }

            // Cross-currency conversion (use locked rate if provided, otherwise current rate)
            $convertedAmount = $amount;
            $exchangeRate = null;
            if ($fromAccount->currency !== $toAccount->currency) {
                if ($lockedExchangeRate) {
                    // Use the rate that was locked when the transfer was created
                    $exchangeRate = $lockedExchangeRate;
                    if ($fromAccount->currency === 'USD' && $toAccount->currency === 'IQD') {
                        $convertedAmount = round($amount * $exchangeRate, 0);
                    } else {
                        $convertedAmount = round($amount / $exchangeRate, 2);
                    }
                } else {
                    $convertedAmount = ExchangeRateService::convert($amount, $fromAccount->currency, $toAccount->currency);
                    $exchangeRate = ExchangeRateService::getRate();
                }
            }

            $reference = Transaction::generateReference();
            $creditRef = Transaction::generateReference();

            // Debit transaction (from held funds)
            $debitTxn = Transaction::create([
                'account_id' => $fromAccount->id,
                'reference_number' => $reference,
                'type' => 'transfer_out',
                'amount' => $amount,
                'currency' => $fromAccount->currency,
                'balance_before' => $fromAccount->balance,
                'balance_after' => $fromAccount->balance - $amount,
                'status' => 'completed',
                'description' => $description,
                'recipient_account_id' => $toAccount->id,
                'recipient_name' => $toAccount->user->name,
                'channel' => 'web',
                'ip_address' => request()->ip(),
                'completed_at' => now(),
            ]);

            // Ledger entry for sender (on current branch)
            \App\Models\LedgerEntry::create([
                'transaction_reference' => $reference,
                'account_id' => $fromAccount->id,
                'type' => 'debit',
                'amount' => $amount,
                'currency' => $fromAccount->currency,
            ]);

            // Update sender balance (on current branch)
            $fromAccount->update([
                'balance' => $fromAccount->balance - $amount,
                'hold_amount' => max(0, $fromAccount->hold_amount - $amount),
            ]);

            // ── Write credit-side data to RECEIVER's branch ──
            $receiverBranch = DistributedDatabaseService::findUserBranchById($toAccount->user_id);
            $receiverConnection = $receiverBranch
                ? DistributedDatabaseService::connectionForBranch($receiverBranch)
                : DistributedDatabaseService::getHqConnection();

            $creditTxnData = [
                'account_id' => $toAccount->id,
                'reference_number' => $creditRef,
                'type' => 'transfer_in',
                'amount' => $convertedAmount,
                'currency' => $toAccount->currency,
                'balance_before' => $toAccount->balance,
                'balance_after' => $toAccount->balance + $convertedAmount,
                'status' => 'completed',
                'description' => $description,
                'recipient_account_id' => $fromAccount->id,
                'recipient_name' => $fromAccount->user->name,
                'channel' => 'web',
                'ip_address' => request()->ip(),
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $creditLedgerData = [
                'transaction_reference' => $creditRef,
                'account_id' => $toAccount->id,
                'type' => 'credit',
                'amount' => $convertedAmount,
                'currency' => $toAccount->currency,
                'created_at' => now(),
            ];

            try {
                DB::connection($receiverConnection)->table('transactions')->insert($creditTxnData);
                DB::connection($receiverConnection)->table('ledger_entries')->insert($creditLedgerData);
                DB::connection($receiverConnection)->table('accounts')
                    ->where('id', $toAccount->id)
                    ->update([
                        'balance' => $toAccount->balance + $convertedAmount,
                        'available_balance' => $toAccount->available_balance + $convertedAmount,
                        'updated_at' => now(),
                    ]);
            } catch (\Exception $e) {
                \Log::warning("Failed to write credit transaction to receiver branch: {$e->getMessage()}");
                // Fallback: write to HQ
                $hqConnection = DistributedDatabaseService::getHqConnection();
                DB::connection($hqConnection)->table('transactions')->insert($creditTxnData);
                DB::connection($hqConnection)->table('ledger_entries')->insert($creditLedgerData);
                DB::connection($hqConnection)->table('accounts')
                    ->where('id', $toAccount->id)
                    ->update([
                        'balance' => $toAccount->balance + $convertedAmount,
                        'available_balance' => $toAccount->available_balance + $convertedAmount,
                        'updated_at' => now(),
                    ]);
            }

            // Also write credit transaction to HQ for admin visibility
            try {
                $hqConnection = DistributedDatabaseService::getHqConnection();
                if ($receiverConnection !== $hqConnection) {
                    DB::connection($hqConnection)->table('transactions')->insert($creditTxnData);
                    DB::connection($hqConnection)->table('ledger_entries')->insert($creditLedgerData);
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to write credit transaction to HQ: {$e->getMessage()}");
            }

            // Create a placeholder creditTxn object for return
            $creditTxn = new Transaction($creditTxnData);
            $creditTxn->id = 0; // Not from local DB

            // Dispatch event
            event(new \App\Events\TransactionCompleted($debitTxn, 'transfer', [
                'model_type' => 'Transaction',
                'model_id' => $debitTxn->id,
                'new_values' => [
                    'from_account' => $fromAccount->account_number,
                    'to_account' => $toAccount->account_number,
                    'amount' => $amount,
                    'converted_amount' => $convertedAmount,
                    'exchange_rate' => $exchangeRate,
                    'reference' => $reference,
                    'type' => 'held_transfer_executed',
                ],
                'severity' => $amount > 5000 ? 'high' : 'medium',
            ]));

            return [
                'debit_transaction' => $debitTxn,
                'credit_transaction' => $creditTxn,
                'reference' => $reference,
                'exchange_rate' => $exchangeRate,
                'converted_amount' => $convertedAmount,
            ];
        });
    }

    /**
     * Check daily and monthly transfer limits.
     */
    private function checkTransferLimits(Account $account, float $amount): void
    {
        $symbol = $account->currencyModel()?->symbol ?? $account->currency;
        $decimals = $account->currencyModel()?->decimal_places ?? 2;

        // Daily limit check
        $dailyTotal = Transaction::where('account_id', $account->id)
            ->where('type', 'transfer_out')
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');

        if (($dailyTotal + $amount) > $account->daily_transfer_limit) {
            throw new \RuntimeException(
                "Daily transfer limit exceeded. Limit: {$symbol} " . number_format($account->daily_transfer_limit, $decimals) .
                ", Used today: {$symbol} " . number_format($dailyTotal, $decimals)
            );
        }

        // Monthly limit check
        $monthlyTotal = Transaction::where('account_id', $account->id)
            ->where('type', 'transfer_out')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        if (($monthlyTotal + $amount) > $account->monthly_transfer_limit) {
            throw new \RuntimeException(
                "Monthly transfer limit exceeded. Limit: {$symbol} " . number_format($account->monthly_transfer_limit, $decimals) .
                ", Used this month: {$symbol} " . number_format($monthlyTotal, $decimals)
            );
        }
    }

    /**
     * Process a deposit.
     */
    public function deposit(Account $account, float $amount, string $description = '', string $channel = 'web'): Transaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Deposit amount must be positive.');
        }

        return DB::transaction(function () use ($account, $amount, $description, $channel) {
            $account = Account::lockForUpdate()->find($account->id);

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'reference_number' => Transaction::generateReference(),
                'type' => 'deposit',
                'amount' => $amount,
                'currency' => $account->currency,
                'balance_before' => $account->balance,
                'balance_after' => $account->balance + $amount,
                'status' => 'completed',
                'description' => $description ?: 'Cash deposit',
                'channel' => $channel,
                'ip_address' => request()->ip(),
                'completed_at' => now(),
            ]);

            \App\Models\LedgerEntry::create([
                'transaction_reference' => $transaction->reference_number,
                'account_id' => $account->id,
                'type' => 'credit',
                'amount' => $amount,
                'currency' => $account->currency,
            ]);

            $account->update([
                'balance' => $account->balance + $amount,
                'available_balance' => $account->available_balance + $amount,
            ]);

            event(new \App\Events\TransactionCompleted($transaction, 'deposit', [
                'model_type' => 'Transaction',
                'model_id' => $transaction->id,
                'new_values' => ['amount' => $amount, 'account' => $account->account_number],
                'severity' => 'medium',
            ]));

            return $transaction;
        });
    }

    /**
     * Process a withdrawal.
     */
    public function withdraw(Account $account, float $amount, string $description = '', string $channel = 'web'): Transaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Withdrawal amount must be positive.');
        }

        if ($account->available_balance < $amount) {
            throw new \RuntimeException('Insufficient funds.');
        }

        return DB::transaction(function () use ($account, $amount, $description, $channel) {
            $account = Account::lockForUpdate()->find($account->id);

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'reference_number' => Transaction::generateReference(),
                'type' => 'withdrawal',
                'amount' => $amount,
                'currency' => $account->currency,
                'balance_before' => $account->balance,
                'balance_after' => $account->balance - $amount,
                'status' => 'completed',
                'description' => $description ?: 'Withdrawal',
                'channel' => $channel,
                'ip_address' => request()->ip(),
                'completed_at' => now(),
            ]);

            \App\Models\LedgerEntry::create([
                'transaction_reference' => $transaction->reference_number,
                'account_id' => $account->id,
                'type' => 'debit',
                'amount' => $amount,
                'currency' => $account->currency,
            ]);

            $account->update([
                'balance' => $account->balance - $amount,
                'available_balance' => $account->available_balance - $amount,
            ]);

            event(new \App\Events\TransactionCompleted($transaction, 'withdraw', [
                'model_type' => 'Transaction',
                'model_id' => $transaction->id,
                'new_values' => ['amount' => $amount, 'account' => $account->account_number],
                'severity' => 'medium',
            ]));

            return $transaction;
        });
    }
}

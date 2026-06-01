<?php

namespace App\Services;

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountService
{
    /**
     * Create a new bank account for a user.
     * Wrapped in a DB transaction for safety.
     */
    public function createAccount(User $user, string $type = 'savings', string $currency = 'USD', bool $isPrimary = false): Account
    {
        return DB::transaction(function () use ($user, $type, $currency, $isPrimary) {
            // First account is automatically primary
            $existingCount = $user->accounts()->count();
            if ($existingCount === 0) {
                $isPrimary = true;
            }

            // Unset existing primary if this one is primary
            if ($isPrimary) {
                $user->accounts()->where('is_primary', true)->update(['is_primary' => false]);
            }

            // Generate unique account number with retry
            $accountNumber = $this->generateUniqueAccountNumber();

            $account = Account::create([
                'user_id' => $user->id,
                'account_number' => $accountNumber,
                'account_name' => $user->name . ' - ' . ucfirst($type) . ' (' . $currency . ')',
                'account_type' => $type,
                'currency' => $currency,
                'balance' => 0,
                'available_balance' => 0,
                'is_primary' => $isPrimary,
                'status' => 'active',
                'daily_transfer_limit' => $this->getDefaultDailyLimit($type),
                'monthly_transfer_limit' => $this->getDefaultMonthlyLimit($type),
                'interest_rate' => $this->getDefaultInterestRate($type),
                'opened_at' => now(),
            ]);

            return $account;
        });
    }

    /**
     * Generate a unique account number with retry logic.
     */
    private function generateUniqueAccountNumber(): string
    {
        $maxAttempts = 10;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $number = Account::generateAccountNumber();

            // Check both HQ and local for uniqueness
            $existsInHq = Account::on(DistributedDatabaseService::getHqConnection())
                ->where('account_number', $number)
                ->exists();

            if (!$existsInHq) {
                return $number;
            }
        }

        throw new \RuntimeException('Failed to generate unique account number after ' . $maxAttempts . ' attempts.');
    }

    private function getDefaultDailyLimit(string $type): float
    {
        return match ($type) {
            'savings' => 5000,
            'checking' => 10000,
            'business' => 50000,
            'fixed_deposit' => 0,
            default => 5000,
        };
    }

    private function getDefaultMonthlyLimit(string $type): float
    {
        return match ($type) {
            'savings' => 25000,
            'checking' => 50000,
            'business' => 250000,
            'fixed_deposit' => 0,
            default => 25000,
        };
    }

    private function getDefaultInterestRate(string $type): float
    {
        return match ($type) {
            'savings' => 0.025,
            'checking' => 0.001,
            'business' => 0.005,
            'fixed_deposit' => 0.045,
            default => 0,
        };
    }
}

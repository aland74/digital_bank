<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Beneficiary;
use App\Models\Card;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Notification;
use App\Services\AccountService;
use App\Services\DistributedDatabaseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $accountService = new AccountService();

        // ── Demo Customer 1 — Erbil Branch ─────────────────────
        DistributedDatabaseService::setActiveBranch('erbil');

        $john = User::create([
            'name' => 'Karwan Ahmad',
            'email' => 'karwan@demo.com',
            'password' => bcrypt('Demo@12345'),
            'phone' => '+964-750-100-2001',
            'national_id' => 'IQ-12345678',
            'date_of_birth' => '1990-06-15',
            'address_line_1' => '60m Street, Ankawa',
            'city' => 'Erbil',
            'state' => 'Erbil',
            'country' => 'IQ',
            'postal_code' => '44001',
            'branch' => 'erbil',
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $johnSavings = $accountService->createAccount($john, 'savings', 'USD', true);
        $johnChecking = $accountService->createAccount($john, 'checking', 'USD');
        $this->seedTransactions($johnSavings, 25000.00, 8);
        $this->seedTransactions($johnChecking, 8500.00, 5);

        Card::create([
            'account_id' => $johnChecking->id, 'user_id' => $john->id,
            'card_number_last4' => '4521',
            'card_number_encrypted' => Crypt::encryptString('4532015112830366'),
            'card_type' => 'debit', 'card_brand' => 'visa', 'cardholder_name' => 'KARWAN AHMAD',
            'expiry_month' => '09', 'expiry_year' => '2028',
            'cvv_encrypted' => Crypt::encryptString('123'),
            'status' => 'active', 'daily_limit' => 5000, 'monthly_limit' => 25000,
            'daily_spent' => 350, 'is_contactless' => true, 'is_online_enabled' => true,
            'is_international_enabled' => false, 'activated_at' => now()->subMonths(3),
        ]);

        Beneficiary::create([
            'user_id' => $john->id, 'name' => 'Shilan Ali', 'nickname' => 'Shilan',
            'account_number' => 'NXB0000000003', 'type' => 'internal',
            'currency' => 'USD', 'is_favorite' => true, 'is_verified' => true,
        ]);

        $loan = Loan::create([
            'user_id' => $john->id, 'account_id' => $johnSavings->id,
            'loan_number' => 'LN00000001', 'loan_type' => 'personal',
            'amount' => 15000, 'interest_rate' => 8.50, 'term_months' => 24,
            'monthly_payment' => 683.37, 'total_interest' => 1400.88,
            'total_paid' => 4100.22, 'remaining_balance' => 12300.66,
            'status' => 'active',
            'applied_at' => now()->subMonths(8),
            'approved_at' => now()->subMonths(8)->addDays(1),
            'disbursed_at' => now()->subMonths(8)->addDays(2),
            'maturity_date' => now()->addMonths(16),
        ]);

        for ($i = 1; $i <= 24; $i++) {
            LoanRepayment::create([
                'loan_id' => $loan->id, 'installment_number' => $i,
                'amount' => 683.37, 'principal' => 625 - ($i * 2),
                'interest' => 58.37 + ($i * 2),
                'remaining_balance' => max(0, 15000 - ($i * 625)),
                'due_date' => now()->subMonths(8)->addMonths($i),
                'paid_at' => $i <= 6 ? now()->subMonths(8)->addMonths($i) : null,
                'status' => $i <= 6 ? 'paid' : ($i === 7 ? 'due' : 'upcoming'),
            ]);
        }

        Notification::create(['user_id' => $john->id, 'title' => 'Welcome to NexusBank!', 'message' => 'Your account at the Erbil branch has been created successfully.', 'type' => 'success', 'is_read' => true, 'read_at' => now()->subDays(5)]);
        Notification::create(['user_id' => $john->id, 'title' => 'Loan Approved', 'message' => 'Your personal loan of $15,000 has been approved and disbursed.', 'type' => 'success', 'is_read' => true, 'read_at' => now()->subDays(3)]);

        // ── Demo Customer 2 — Sulaimaniyah Branch ──────────────
        DistributedDatabaseService::setActiveBranch('sulaimaniyah');

        $emily = User::create([
            'name' => 'Shilan Ali',
            'email' => 'shilan@demo.com',
            'password' => bcrypt('Demo@12345'),
            'phone' => '+964-770-200-3001',
            'date_of_birth' => '1988-03-22',
            'address_line_1' => 'Salim Street, Sulaymaniyah',
            'city' => 'Sulaimaniyah',
            'state' => 'Sulaimaniyah',
            'country' => 'IQ',
            'postal_code' => '46001',
            'branch' => 'sulaimaniyah',
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $emilySavings = $accountService->createAccount($emily, 'savings', 'USD', true);
        $emilyBusiness = $accountService->createAccount($emily, 'business', 'USD');
        $this->seedTransactions($emilySavings, 42000.00, 12);
        $this->seedTransactions($emilyBusiness, 125000.00, 15);

        Card::create([
            'account_id' => $emilySavings->id, 'user_id' => $emily->id,
            'card_number_last4' => '7723',
            'card_number_encrypted' => Crypt::encryptString('4532015112837723'),
            'card_type' => 'debit', 'card_brand' => 'visa', 'cardholder_name' => 'SHILAN ALI',
            'expiry_month' => '03', 'expiry_year' => '2029',
            'cvv_encrypted' => Crypt::encryptString('789'),
            'status' => 'active', 'daily_limit' => 5000, 'monthly_limit' => 25000,
            'is_contactless' => true, 'is_online_enabled' => true,
            'activated_at' => now()->subMonths(2),
        ]);

        Notification::create(['user_id' => $emily->id, 'title' => 'Welcome to NexusBank!', 'message' => 'Your account at the Sulaimaniyah branch has been created successfully.', 'type' => 'success', 'is_read' => true, 'read_at' => now()->subDays(5)]);

        // ── Demo Customer 3 — Duhok Branch (pending verification) ──
        DistributedDatabaseService::setActiveBranch('duhok');

        $pending = User::create([
            'name' => 'Heman Barzan',
            'email' => 'heman@demo.com',
            'password' => bcrypt('Demo@12345'),
            'phone' => '+964-762-300-4001',
            'branch' => 'duhok',
            'role' => 'customer',
            'status' => 'pending_verification',
            'country' => 'IQ',
        ]);
        $accountService->createAccount($pending, 'savings', 'USD', true);

        Notification::create(['user_id' => $pending->id, 'title' => 'Welcome to NexusBank!', 'message' => 'Your account at the Duhok branch has been created. Please upload your documents.', 'type' => 'info', 'is_read' => false]);

        // Reset to HQ
        DistributedDatabaseService::setHQ();
    }

    private function seedTransactions(Account $account, float $targetBalance, int $count): void
    {
        $types = ['deposit', 'deposit', 'deposit', 'withdrawal', 'payment', 'interest'];
        $descriptions = [
            'deposit' => ['Salary deposit', 'Freelance payment', 'Client payment', 'Investment return', 'Refund received', 'Cash deposit'],
            'withdrawal' => ['ATM Withdrawal', 'Cash withdrawal'],
            'payment' => ['Online shopping', 'Subscription', 'Taxi ride', 'Grocery store', 'Electric bill', 'Gas station'],
            'interest' => ['Monthly interest'],
        ];

        $balance = 0;
        $perTransaction = $targetBalance / max($count * 0.6, 1);

        for ($i = 0; $i < $count; $i++) {
            $type = $types[array_rand($types)];
            $amount = match($type) {
                'deposit' => round(rand(intval($perTransaction * 0.5), intval($perTransaction * 1.5)), 2),
                'withdrawal' => round(rand(50, 500), 2),
                'payment' => round(rand(10, 300), 2),
                'interest' => round($balance * 0.002, 2),
                default => round(rand(100, 1000), 2),
            };

            if ($amount <= 0) $amount = 50;
            if (in_array($type, ['withdrawal', 'payment']) && $amount > $balance) {
                $type = 'deposit';
                $amount = round(rand(500, 3000), 2);
            }

            $balanceBefore = $balance;
            $balance = in_array($type, ['deposit', 'interest'])
                ? $balance + $amount
                : $balance - $amount;

            $desc = $descriptions[$type] ?? ['Transaction'];

            Transaction::create([
                'account_id' => $account->id,
                'reference_number' => Transaction::generateReference(),
                'type' => $type,
                'amount' => $amount,
                'currency' => $account->currency,
                'balance_before' => $balanceBefore,
                'balance_after' => $balance,
                'status' => 'completed',
                'description' => $desc[array_rand($desc)],
                'channel' => ['web', 'mobile', 'atm'][array_rand(['web', 'mobile', 'atm'])],
                'completed_at' => now()->subDays($count - $i + rand(0, 5)),
                'created_at' => now()->subDays($count - $i + rand(0, 5)),
            ]);
        }

        $remaining = $targetBalance - $balance;
        if ($remaining > 0) {
            Transaction::create([
                'account_id' => $account->id, 'reference_number' => Transaction::generateReference(),
                'type' => 'deposit', 'amount' => $remaining, 'currency' => $account->currency,
                'balance_before' => $balance, 'balance_after' => $targetBalance,
                'status' => 'completed', 'description' => 'Direct deposit',
                'channel' => 'system', 'completed_at' => now()->subDay(),
                'created_at' => now()->subDay(),
            ]);
        }

        $account->update(['balance' => $targetBalance, 'available_balance' => $targetBalance]);
    }
}

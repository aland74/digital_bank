<?php

use App\Models\User;
use App\Models\Account;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::createFromGlobals());

echo "Starting Currency Conversion Limit Verification...\n";

try {
    // 1. Setup Test User on Erbil Branch
    $branch = 'erbil';
    $connection = 'mysql_erbil';

    // Clean up previous test user
    DB::connection($connection)->table('users')->where('email', 'limit.test@bank.com')->delete();
    DB::connection($connection)->table('accounts')->where('user_id', function($q) {
        $q->select('id')->from('users')->where('email', 'limit.test@bank.com');
    })->delete();

    $user = User::on($connection)->create([
        'name' => 'Limit Test User',
        'email' => 'limit.test@bank.com',
        'password' => Hash::make('password'),
        'branch' => $branch,
        'status' => 'active',
    ]);

    $usdAccount = Account::on($connection)->create([
        'user_id' => $user->id,
        'account_number' => 'USD-' . uniqid(),
        'currency' => 'USD',
        'balance' => 1000.00,
        'available_balance' => 1000.00,
        'status' => 'active',
    ]);

    $iqdAccount = Account::on($connection)->create([
        'user_id' => $user->id,
        'account_number' => 'IQD-' . uniqid(),
        'currency' => 'IQD',
        'balance' => 0.00,
        'available_balance' => 0.00,
        'status' => 'active',
    ]);

    $service = app(TransactionService::class);

    echo "User created with USD and IQD accounts on branch: $branch\n";

    // Test 1: Convert $100 (Should pass)
    echo "Test 1: Converting $100 USD... ";
    $service->transfer($usdAccount, $iqdAccount, 100.00, 'Test 1');
    echo "PASS\n";

    // Test 2: Convert $50 (Should pass)
    echo "Test 2: Converting $50 USD... ";
    $service->transfer($usdAccount, $iqdAccount, 50.00, 'Test 2');
    echo "PASS\n";

    // Test 3: Convert $60 (Total $210 -> Should FAIL)
    echo "Test 3: Converting $60 USD (Total $210)... ";
    try {
        $service->transfer($usdAccount, $iqdAccount, 60.00, 'Test 3');
        echo "FAIL (Limit was not enforced!)\n";
        exit(1);
    } catch (\RuntimeException $e) {
        if (str_contains($e->getMessage(), 'Monthly currency conversion limit')) {
            echo "PASS (Correctly blocked: " . $e->getMessage() . ")\n";
        } else {
            echo "FAIL (Blocked but wrong message: " . $e->getMessage() . ")\n";
            exit(1);
        }
    }

    echo "\n✅ VERIFICATION SUCCESSFUL: Monthly conversion limit of $200 is working correctly.\n";

} catch (\Exception $e) {
    echo "\n❌ UNEXPECTED ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}

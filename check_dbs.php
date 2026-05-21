<?php
// Detailed check of data distribution across all databases
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$connections = ['mysql_hq', 'mysql_erbil', 'mysql_sulaimaniyah', 'mysql_duhok'];
$tables = ['users', 'accounts', 'notifications', 'audit_logs', 'bank_settings'];

echo "\n=== Data Distribution Check ===\n\n";
echo str_pad('Table', 20) . str_pad('HQ', 10) . str_pad('Erbil', 10) . str_pad('Suli', 10) . str_pad('Duhok', 10) . "\n";
echo str_repeat('-', 60) . "\n";

foreach ($tables as $table) {
    echo str_pad($table, 20);
    foreach ($connections as $conn) {
        try {
            $count = DB::connection($conn)->table($table)->count();
            echo str_pad($count, 10);
        } catch (\Exception $e) {
            echo str_pad('ERR', 10);
        }
    }
    echo "\n";
}

echo "\n=== Users Detail (HQ) ===\n\n";
$users = DB::connection('mysql_hq')->table('users')->select('id', 'name', 'email', 'branch', 'status', 'role')->get();
foreach ($users as $user) {
    echo "  #{$user->id} | {$user->name} | {$user->email} | branch={$user->branch} | {$user->status} | {$user->role}\n";
}

echo "\n=== Accounts Detail (HQ) ===\n\n";
$accounts = DB::connection('mysql_hq')->table('accounts')->select('id', 'user_id', 'account_number', 'account_type', 'balance', 'status')->get();
foreach ($accounts as $acc) {
    echo "  #{$acc->id} | user_id={$acc->user_id} | {$acc->account_number} | {$acc->account_type} | \${$acc->balance} | {$acc->status}\n";
}

echo "\n";

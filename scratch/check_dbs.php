<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$conns = ['sqlite_hq', 'sqlite_erbil', 'sqlite_sulaimaniyah', 'sqlite_duhok'];
foreach ($conns as $conn) {
    try {
        $count = DB::connection($conn)->table('users')->count();
        echo "Connection '$conn' has $count users.\n";
        
        $users = DB::connection($conn)->table('users')->select('id', 'name', 'email', 'role', 'branch')->get();
        foreach ($users as $u) {
            echo "  - #{$u->id} | {$u->name} | {$u->email} | {$u->role} | {$u->branch}\n";
        }
    } catch (\Exception $e) {
        echo "Connection '$conn' error: " . $e->getMessage() . "\n";
    }
}

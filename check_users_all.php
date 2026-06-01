<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$conns = ['mysql_hq', 'mysql_erbil', 'mysql_sulaimaniyah', 'mysql_duhok'];
foreach ($conns as $conn) {
    try {
        $count = DB::connection($conn)->table('users')->count();
        echo "$conn: $count users\n";
        if ($count > 0) {
            $users = DB::connection($conn)->table('users')->select('id', 'name', 'email', 'branch', 'role')->get();
            foreach ($users as $u) {
                echo "  - #{$u->id} | {$u->name} | {$u->email} | branch={$u->branch} | {$u->role}\n";
            }
        }
    } catch (\Exception $e) {
        echo "$conn error: " . $e->getMessage() . "\n";
    }
}

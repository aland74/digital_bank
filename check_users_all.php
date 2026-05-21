<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$conns = ['mysql_hq', 'sqlite_hq', 'sqlite_erbil', 'sqlite_sulaimaniyah', 'sqlite_duhok'];
foreach ($conns as $conn) {
    try {
        $count = DB::connection($conn)->table('users')->count();
        echo "$conn: $count users\n";
        if ($count > 0) {
            $users = DB::connection($conn)->table('users')->select('id', 'name', 'email', 'role')->get();
            foreach ($users as $u) {
                echo "  - #{$u->id} | {$u->name} | {$u->email} | {$u->role}\n";
            }
        }
    } catch (\Exception $e) {
        echo "$conn error: " . $e->getMessage() . "\n";
    }
}

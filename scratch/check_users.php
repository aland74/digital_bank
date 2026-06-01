<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

$connections = ['mysql_hq', 'mysql_erbil', 'mysql_sulaimaniyah', 'mysql_duhok'];

foreach ($connections as $conn) {
    echo "=== Connection: $conn ===\n";
    try {
        $users = DB::connection($conn)->table('users')->get(['id', 'name', 'email', 'branch', 'role', 'status', 'email_verified_at']);
        foreach ($users as $u) {
            echo "  ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Branch: {$u->branch} | Verified: " . ($u->email_verified_at ? 'YES' : 'NO') . "\n";
        }
    } catch (\Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}

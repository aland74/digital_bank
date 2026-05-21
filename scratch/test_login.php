<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

$email = 'admin@nexusbank.com';
$password = 'Admin@123456';

echo "=== CHECKING sqlite_hq ===\n";
try {
    $user = DB::connection('sqlite_hq')->table('users')->where('email', $email)->first();
    if ($user) {
        $check = Hash::check($password, $user->password);
        echo "User found in sqlite_hq. Password check: " . ($check ? "SUCCESS" : "FAILED") . "\n";
    } else {
        echo "User not found in sqlite_hq!\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECKING mysql_hq ===\n";
try {
    $user = DB::connection('mysql_hq')->table('users')->where('email', $email)->first();
    if ($user) {
        $check = Hash::check($password, $user->password);
        echo "User found in mysql_hq. Password check: " . ($check ? "SUCCESS" : "FAILED") . "\n";
    } else {
        echo "User not found in mysql_hq!\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

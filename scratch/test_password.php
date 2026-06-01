<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

$users = [
    'admin@distributedbank.com' => 'Admin@123456',
    'sara@distributedbank.com' => 'Staff@123456',
    'john.doe@gmail.com' => 'Demo@12345',
    'aland.developer@gmail.com' => 'Demo@12345',
    'sulaimaniyah.admin@gmail.com' => 'Demo@12345',
    'duhok.customer@gmail.com' => 'Demo@12345'
];

foreach ($users as $email => $pass) {
    try {
        $u = DB::connection('sqlite_hq')->table('users')->where('email', $email)->first();
        if ($u) {
            $check = Hash::check($pass, $u->password);
            echo "sqlite_hq: {$email} (role: {$u->role}) | pass check '{$pass}': " . ($check ? "PASS" : "FAIL") . "\n";
        } else {
            echo "sqlite_hq: {$email} NOT found\n";
        }
    } catch (\Exception $e) {
        echo "Error checking {$email}: " . $e->getMessage() . "\n";
    }
}

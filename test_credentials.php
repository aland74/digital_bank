<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

echo "Default DB Connection: " . Config::get('database.default') . "\n";
echo "DB_CONNECTION env: " . env('DB_CONNECTION') . "\n";

foreach (['sqlite_hq', 'mysql_hq'] as $conn) {
    try {
        $user = DB::connection($conn)->table('users')->where('email', 'admin@distributedbank.com')->first();
        if ($user) {
            $check = Hash::check('Admin@123456', $user->password);
            echo "Connection '$conn' check: " . ($check ? "SUCCESS ✅" : "FAILED ❌") . "\n";
            echo "  Name: " . $user->name . "\n";
            echo "  Role: " . $user->role . "\n";
            echo "  Status: " . $user->status . "\n";
        } else {
            echo "Connection '$conn' check: Admin not found! ❌\n";
        }
    } catch (\Exception $e) {
        echo "Connection '$conn' error: " . $e->getMessage() . "\n";
    }
}

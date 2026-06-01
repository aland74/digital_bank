<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

echo "Default DB: " . Config::get('database.default') . "\n";

// Check admin
$user = User::where('email', 'admin@distributedbank.com')->first();
if ($user) {
    echo "Found admin user: {$user->name}\n";
    echo "Password hash prefix: " . substr($user->password, 0, 7) . "\n";
    echo "Hash check 'Admin@123456': " . (Hash::check('Admin@123456', $user->password) ? 'YES ✅' : 'NO ❌') . "\n";
    
    // Check if password was double-hashed (bcrypt in seeder + hashed cast in model)
    echo "Password length: " . strlen($user->password) . "\n";
} else {
    echo "Admin not found ❌\n";
}

// Check demo user
$demo = User::where('email', 'karwan@demo.com')->first();
if ($demo) {
    echo "\nFound demo user: {$demo->name}\n";
    echo "Hash check 'Demo@12345': " . (Hash::check('Demo@12345', $demo->password) ? 'YES ✅' : 'NO ❌') . "\n";
    echo "Password length: " . strlen($demo->password) . "\n";
} else {
    echo "\nDemo user not found ❌\n";
}

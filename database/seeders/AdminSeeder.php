<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AccountService;
use App\Services\DistributedDatabaseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $accountService = new AccountService();

        // Admins are written to HQ — they operate globally
        Config::set('database.default', DistributedDatabaseService::getHqConnection());

        // Super Admin
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@distributedbank.com',
            'password' => bcrypt('Admin@123456'),
            'phone' => '+964-750-000-0001',
            'role' => 'super_admin',
            'status' => 'active',
            'branch' => 'erbil',
            'email_verified_at' => now(),
            'country' => 'IQ',
        ]);
        $accountService->createAccount($admin, 'checking', 'USD', true);

        // Regular Admin
        $staff = User::create([
            'name' => 'Sara Ahmed',
            'email' => 'sara@distributedbank.com',
            'password' => bcrypt('Staff@123456'),
            'phone' => '+964-770-000-0002',
            'role' => 'admin',
            'status' => 'active',
            'branch' => 'sulaimaniyah',
            'email_verified_at' => now(),
            'country' => 'IQ',
        ]);
        $accountService->createAccount($staff, 'checking', 'USD', true);
    }
}

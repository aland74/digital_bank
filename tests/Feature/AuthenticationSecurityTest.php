<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Services\DistributedDatabaseService;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $testDbDir = storage_path('app/test_dbs_auth');
        if (!File::exists($testDbDir)) {
            File::makeDirectory($testDbDir, 0755, true);
        }

        $files = [
            'sqlite_hq' => $testDbDir . '/test_hq.sqlite',
            'sqlite_erbil' => $testDbDir . '/test_erbil.sqlite',
            'sqlite_sulaimaniyah' => $testDbDir . '/test_sulaimaniyah.sqlite',
            'sqlite_duhok' => $testDbDir . '/test_duhok.sqlite',
        ];

        foreach ($files as $conn => $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
            File::put($path, ''); // Create empty file
            
            Config::set("database.connections.{$conn}", [
                'driver' => 'sqlite',
                'database' => $path,
                'prefix' => '',
            ]);
        }

        // Set the default to SQLite HQ for bootstrap migrations
        Config::set('database.default', 'sqlite_hq');

        // Migrate all databases fresh
        Artisan::call('migrate:fresh', ['--database' => 'sqlite_hq', '--force' => true]);
        Artisan::call('migrate:fresh', ['--database' => 'sqlite_erbil', '--force' => true]);
        Artisan::call('migrate:fresh', ['--database' => 'sqlite_sulaimaniyah', '--force' => true]);
        Artisan::call('migrate:fresh', ['--database' => 'sqlite_duhok', '--force' => true]);

        // Ensure we are working on the sqlite driver for testing
        DistributedDatabaseService::setHQ();
    }

    protected function tearDown(): void
    {
        // Disconnect to release locks on SQLite files
        DB::disconnect('sqlite_hq');
        DB::disconnect('sqlite_erbil');
        DB::disconnect('sqlite_sulaimaniyah');
        DB::disconnect('sqlite_duhok');

        $testDbDir = storage_path('app/test_dbs_auth');
        if (File::exists($testDbDir)) {
            File::deleteDirectory($testDbDir);
        }

        parent::tearDown();
    }

    /**
     * Test case-insensitive email uniqueness check across branch databases.
     */
    public function test_case_insensitive_email_uniqueness_validation()
    {
        // 1. Create a user on Sulaimaniyah branch first
        DistributedDatabaseService::setActiveBranch('sulaimaniyah');
        $user = User::create([
            'name' => 'Ala Suly',
            'email' => 'ala@gmail.com',
            'phone' => '+9647501111111',
            'password' => 'Password123!',
            'branch' => 'sulaimaniyah',
            'status' => 'active',
            'role' => 'customer',
        ]);



        // 2. Attempt to register a user with same email (case variation) on Duhok
        $response = $this->post('/register', [
            'name' => 'Ala Duhok',
            'email' => 'ALA@gmail.com', // uppercase variation
            'phone' => '+9647502222222',
            'branch' => 'duhok',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => 'on',
        ]);

        // 3. Verify it is rejected with correct validation errors rather than SQL uniqueness violation
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            'The email has already been taken.',
            session('errors')->first('email')
        );
    }

    /**
     * Test manual registration OTP sequence.
     */
    public function test_manual_registration_otp_verification_sequence()
    {
        // 1. Trigger registration
        $response = $this->post('/register', [
            'name' => 'John OTP',
            'email' => 'john.otp@example.com',
            'phone' => '+9647503333333',
            'branch' => 'erbil',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => 'on',
        ]);

        // 2. Verify redirect to verify-otp page and session keys
        $response->assertRedirect(route('auth.verify-otp'));
        $this->assertCredentials([
            'email' => 'john.otp@example.com',
            'password' => 'SecurePass123!',
        ]);

        $userId = session('pending_user_id');
        $this->assertNotNull($userId);

        // Verify user is unverified and pending verification
        DistributedDatabaseService::setHQ();
        $user = User::find($userId);
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertEquals('pending_verification', $user->status);

        // 3. Fetch active OTP from Cache
        $cacheKey = 'otp_' . $user->id;
        $otp = Cache::get($cacheKey);
        $this->assertNotNull($otp);
        $this->assertEquals(6, strlen($otp));

        // Try submitting invalid OTP first
        $verifyResponse = $this->withSession(['pending_user_id' => $user->id])
            ->post('/auth/verify-otp', ['otp' => '000000']);
        
        $verifyResponse->assertSessionHasErrors(['otp']);

        // Try submitting correct OTP
        $verifyResponse = $this->withSession(['pending_user_id' => $user->id])
            ->post('/auth/verify-otp', ['otp' => $otp]);

        // Verify successful verification and account creation
        $verifyResponse->assertRedirect(route('dashboard'));
        
        // Reload user
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals('active', $user->status);

        // Check primary account is created
        DistributedDatabaseService::setActiveBranch('erbil');
        $account = Account::where('user_id', $user->id)->where('is_primary', true)->first();
        $this->assertNotNull($account);
        $this->assertEquals('savings', $account->account_type);
        $this->assertEquals('USD', $account->currency);
    }

    /**
     * Test Google Simulated Sandbox Login (existing user).
     */
    public function test_google_simulated_sandbox_login_existing_user()
    {
        // 1. Pre-register a customer on Erbil
        DistributedDatabaseService::setActiveBranch('erbil');
        $user = User::create([
            'name' => 'Erbil Google User',
            'email' => 'erbil.google@gmail.com',
            'password' => 'HashedPass123!',
            'branch' => 'erbil',
            'status' => 'active',
            'email_verified_at' => now(),
            'role' => 'customer',
        ]);

        // 2. Submit simulated Google Sign-in
        $response = $this->post('/auth/google/callback/simulated', [
            'email' => 'erbil.google@gmail.com',
            'name' => 'Erbil Google User',
        ]);

        // 3. Verify direct dashboard redirect and active session
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test Google Simulated Sandbox Registration (new user).
     */
    public function test_google_simulated_sandbox_registration_new_user()
    {
        // 1. Submit simulated Google Sign-in for a brand new user
        $response = $this->post('/auth/google/callback/simulated', [
            'email' => 'new.google@gmail.com',
            'name' => 'New Google User',
            'branch' => 'duhok',
        ]);

        // 2. Verify redirect to verification screen
        $response->assertRedirect(route('auth.verify-otp'));

        $userId = session('pending_user_id');
        $this->assertNotNull($userId);

        DistributedDatabaseService::setHQ();
        $user = User::find($userId);
        $this->assertNotNull($user);
        $this->assertEquals('new.google@gmail.com', $user->email);
        $this->assertEquals('duhok', $user->branch);
        $this->assertEquals('pending_verification', $user->status);
        $this->assertNull($user->email_verified_at);

        // Fetch active OTP from Cache
        $cacheKey = 'otp_' . $user->id;
        $otp = Cache::get($cacheKey);
        $this->assertNotNull($otp);
        $this->assertEquals(6, strlen($otp));

        // Submit the OTP
        $verifyResponse = $this->withSession(['pending_user_id' => $user->id])
            ->post('/auth/verify-otp', ['otp' => $otp]);

        // Verify redirect to dashboard after verification
        $verifyResponse->assertRedirect(route('dashboard'));

        // Refresh user and assert active
        $user->refresh();
        $this->assertEquals('active', $user->status);
        $this->assertNotNull($user->email_verified_at);

        $this->assertAuthenticatedAs($user);

        // Verify savings account is generated
        DistributedDatabaseService::setActiveBranch('duhok');
        $account = Account::where('user_id', $user->id)->first();
        $this->assertNotNull($account);
    }
}

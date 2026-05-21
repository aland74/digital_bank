<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DistributedDatabaseService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseResilienceAndRoleIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $testDbDir = storage_path('app/test_dbs');
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
    }

    protected function tearDown(): void
    {
        // Disconnect to release locks on SQLite files
        DB::disconnect('sqlite_hq');
        DB::disconnect('sqlite_erbil');
        DB::disconnect('sqlite_sulaimaniyah');
        DB::disconnect('sqlite_duhok');

        $testDbDir = storage_path('app/test_dbs');
        if (File::exists($testDbDir)) {
            File::deleteDirectory($testDbDir);
        }

        parent::tearDown();
    }

    /**
     * Test role isolation boundaries.
     */
    public function test_role_isolation_boundaries()
    {
        // Ensure default connection is HQ for seeding admin users
        Config::set('database.default', 'sqlite_hq');

        // 1. Create a Super Admin and a regular Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@nexusbank.com',
            'password' => bcrypt('Password123!'),
            'role' => 'super_admin',
            'status' => 'active',
            'branch' => 'erbil',
        ]);

        $regularAdmin = User::create([
            'name' => 'Regular Admin',
            'email' => 'regular@nexusbank.com',
            'password' => bcrypt('Password123!'),
            'role' => 'admin',
            'status' => 'active',
            'branch' => 'sulaimaniyah',
        ]);

        // 2. Test accessing /admin/settings as Regular Admin (view only - allowed)
        $response = $this->actingAs($regularAdmin)->get('/admin/settings');
        $response->assertStatus(200); // All admins can view settings

        // 3. Test accessing /admin/audit-logs as Regular Admin (super_admin only)
        $response = $this->actingAs($regularAdmin)->get('/admin/audit-logs');
        $response->assertStatus(403);

        // 4. Test accessing as Super Admin
        $response = $this->actingAs($superAdmin)->get('/admin/settings');
        $response->assertStatus(200);

        $response = $this->actingAs($superAdmin)->get('/admin/audit-logs');
        $response->assertStatus(200);
    }

    /**
     * Test database backup generation.
     */
    public function test_database_backup_generation()
    {
        // Ensure the backups directory is clean
        $backupDir = storage_path('app/backups');
        if (File::exists($backupDir)) {
            File::deleteDirectory($backupDir);
        }

        // Run the backup command
        $exitCode = Artisan::call('db:backup');
        $this->assertEquals(0, $exitCode);

        // Assert that backups directory exists and has files
        $this->assertTrue(File::exists($backupDir));
        $files = File::files($backupDir);
        $this->assertNotEmpty($files);

        // Clean up
        File::deleteDirectory($backupDir);
    }

    /**
     * Test outbox mechanism and recovery sync.
     */
    public function test_outbox_failover_and_recovery_sync()
    {
        // Set connection default to sqlite_erbil
        Config::set('database.default', 'sqlite_erbil');

        // Verify databases are clean
        $this->assertEquals(0, DB::connection('sqlite_hq')->table('users')->count());
        $this->assertEquals(0, DB::connection('sqlite_erbil')->table('users')->count());

        // 1. Write when both HQ and Branch are online
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'branch' => 'erbil',
        ]);

        // Replicated to both
        $this->assertEquals(1, DB::connection('sqlite_hq')->table('users')->count());
        $this->assertEquals(1, DB::connection('sqlite_erbil')->table('users')->count());

        // 2. Simulate HQ down
        // Change the database file path to a non-existent file path inside a non-existent folder
        Config::set('database.connections.sqlite_hq.database', '/nonexistent_folder/nonexistent_file.sqlite');
        DB::purge('sqlite_hq'); // Clear connection cache

        $this->assertFalse(DistributedDatabaseService::isConnectionOnline('sqlite_hq'));

        // Write a new user in Erbil
        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'branch' => 'erbil',
        ]);

        // Branch has the user, HQ doesn't (because it's down), and it's queued in pending_hq_syncs
        $this->assertEquals(2, DB::connection('sqlite_erbil')->table('users')->count());
        
        $pending = DB::connection('sqlite_erbil')->table('pending_hq_syncs')->get();
        $this->assertEquals(1, $pending->count());
        $this->assertEquals('users', $pending->first()->table);
        $this->assertEquals($user2->id, $pending->first()->record_id);

        // 3. Restore HQ
        $testDbDir = storage_path('app/test_dbs');
        $hqPath = $testDbDir . '/test_hq.sqlite';
        Config::set('database.connections.sqlite_hq.database', $hqPath);
        DB::purge('sqlite_hq');
        
        $this->assertTrue(DistributedDatabaseService::isConnectionOnline('sqlite_hq'));

        // 4. Run Sync Command to heal the system
        $exitCode = Artisan::call('db:sync-distributed');
        $this->assertEquals(0, $exitCode);

        // Verify outbox is cleared
        $this->assertEquals(0, DB::connection('sqlite_erbil')->table('pending_hq_syncs')->count());

        // Verify HQ now has the synced record
        $this->assertTrue(DB::connection('sqlite_hq')->table('users')->where('email', 'user2@example.com')->exists());
    }
}

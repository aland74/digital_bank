<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $testDbDir = storage_path('app/test_dbs_idem');
        if (!\Illuminate\Support\Facades\File::exists($testDbDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($testDbDir, 0755, true);
        }

        $files = [
            'sqlite_hq' => $testDbDir . '/test_hq.sqlite',
            'sqlite_erbil' => $testDbDir . '/test_erbil.sqlite',
            'sqlite_sulaimaniyah' => $testDbDir . '/test_sulaimaniyah.sqlite',
            'sqlite_duhok' => $testDbDir . '/test_duhok.sqlite',
        ];

        foreach ($files as $conn => $path) {
            if (\Illuminate\Support\Facades\File::exists($path)) {
                \Illuminate\Support\Facades\File::delete($path);
            }
            \Illuminate\Support\Facades\File::put($path, ''); // Create empty file
            
            \Illuminate\Support\Facades\Config::set("database.connections.{$conn}", [
                'driver' => 'sqlite',
                'database' => $path,
                'prefix' => '',
            ]);
        }

        // Set the default to SQLite HQ for bootstrap migrations
        \Illuminate\Support\Facades\Config::set('database.default', 'sqlite_hq');

        // Migrate all databases fresh
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--database' => 'sqlite_hq', '--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--database' => 'sqlite_erbil', '--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--database' => 'sqlite_sulaimaniyah', '--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--database' => 'sqlite_duhok', '--force' => true]);

        // Ensure we are working on the sqlite driver for testing
        \App\Services\DistributedDatabaseService::setHQ();
    }

    protected function tearDown(): void
    {
        // Disconnect to release locks on SQLite files
        \Illuminate\Support\Facades\DB::disconnect('sqlite_hq');
        \Illuminate\Support\Facades\DB::disconnect('sqlite_erbil');
        \Illuminate\Support\Facades\DB::disconnect('sqlite_sulaimaniyah');
        \Illuminate\Support\Facades\DB::disconnect('sqlite_duhok');

        $testDbDir = storage_path('app/test_dbs_idem');
        if (\Illuminate\Support\Facades\File::exists($testDbDir)) {
            \Illuminate\Support\Facades\File::deleteDirectory($testDbDir);
        }

        parent::tearDown();
    }

    /**
     * A basic feature test example.
     */
    public function test_api_requests_with_same_idempotency_key_return_same_response()
    {
        $key = 'test-idempotency-key-' . uniqid();
        
        $response1 = $this->withHeaders([
            'Idempotency-Key' => $key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john' . uniqid() . '@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'national_id' => '123456789' . rand(100, 999), 
            'phone' => '1234567890' . rand(10, 99),
            'branch' => 'erbil',
        ]);
        
        $response1->assertStatus(201); // Assuming 201 Created for register
        
        // Second request with exact same payload but the database might reject if email was unique, 
        // however idempotency should kick in BEFORE that
        $response2 = $this->withHeaders([
            'Idempotency-Key' => $key,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/auth/register', [
             // Even if we send different data, the idempotency key should match the first response
             'name' => 'Diff Name',
        ]);
        
        $response2->assertStatus(201);
        $this->assertEquals($response1->getContent(), $response2->getContent());
        
        // Verify it was logged in the DB
        $this->assertDatabaseHas('idempotent_requests', [
            'key' => $key,
            'response_code' => 201,
        ]);
    }
}

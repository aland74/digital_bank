<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;
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

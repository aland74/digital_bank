<?php

namespace Tests\Unit;

use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_otp_generation_and_verification(): void
    {
        $user = new \App\Models\User();
        $user->id = 999;
        $user->email = 'test@example.com';

        $service = new \App\Services\OtpService();
        
        $otp = $service->generateOtp($user);
        $this->assertNotEmpty($otp);
        $this->assertEquals(6, strlen($otp));
        
        $this->assertTrue($service->verifyOtp($user, $otp));
        
        // Verifying again should fail because it clears the cache
        $this->assertFalse($service->verifyOtp($user, $otp));
    }
}

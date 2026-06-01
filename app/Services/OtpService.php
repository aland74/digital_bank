<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Generate and store an OTP for the given user.
     */
    public function generateOtp(User $user): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $cacheKey = 'otp_' . $user->id;
        
        // Store in cache for 5 minutes
        Cache::put($cacheKey, $otp, now()->addMinutes(5));
        
        // Mock sending the OTP via Email/SMS by logging it
        Log::info("Generated OTP for User {$user->id} ({$user->email}): {$otp}");
        
        return $otp;
    }

    /**
     * Verify the provided OTP for the given user.
     */
    public function verifyOtp(User $user, string $otp): bool
    {
        $cacheKey = 'otp_' . $user->id;
        
        $cachedOtp = Cache::get($cacheKey);
        
        if ($cachedOtp && $cachedOtp === $otp) {
            Cache::forget($cacheKey);
            return true;
        }
        
        return false;
    }

    /**
     * Generate and store a password reset OTP.
     */
    public function generateResetOtp(User $user): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = 'password_reset_otp_' . $user->id;
        
        Cache::put($cacheKey, $otp, now()->addMinutes(10));
        Log::info("Generated Password Reset OTP for User {$user->id} ({$user->email}): {$otp}");
        
        return $otp;
    }

    /**
     * Verify the provided password reset OTP.
     */
    public function verifyResetOtp(User $user, string $otp): bool
    {
        $cacheKey = 'password_reset_otp_' . $user->id;
        $cachedOtp = Cache::get($cacheKey);
        
        if ($cachedOtp && $cachedOtp === $otp) {
            Cache::forget($cacheKey);
            return true;
        }
        
        return false;
    }
}

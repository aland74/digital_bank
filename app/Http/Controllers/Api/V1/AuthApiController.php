<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Services\OtpService;

class AuthApiController extends Controller
{
    public function register(Request $request, AccountService $accountService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'phone' => 'nullable|string|max:20',
            'national_id' => 'nullable|string|max:50',
            'branch' => 'required|in:' . implode(',', \App\Services\DistributedDatabaseService::branchKeys()),
        ]);

        $branch = $validated['branch'];

        // Switch to the branch DB
        try {
            $branchConn = \App\Services\DistributedDatabaseService::connectionForBranch($branch);
            if (\App\Services\DistributedDatabaseService::isConnectionOnline($branchConn)) {
                \App\Services\DistributedDatabaseService::setActiveBranch($branch);
            } else {
                \App\Services\DistributedDatabaseService::setHQFallback($branch);
            }
        } catch (\Exception $e) {
            \App\Services\DistributedDatabaseService::setHQFallback($branch);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'national_id' => $validated['national_id'] ?? ('IQ-' . rand(10000000, 99999999)),
            'branch' => $branch,
            'city' => ucfirst($branch),
            'state' => ucfirst($branch),
            'country' => 'IQ',
            'status' => 'active',
            'role' => 'customer',
        ]);

        // Create both USD and IQD savings accounts on the active branch DB
        $accountService->createAccount($user, 'savings', 'USD', true);
        $accountService->createAccount($user, 'savings', 'IQD', false);

        $token = $user->createToken('mobile-app')->plainTextToken;

        // Reset default connection back to HQ for general safety
        \App\Services\DistributedDatabaseService::setHQ();

        return response()->json([
            'message' => 'Account created successfully.',
            'user' => array_merge($user->only(['id', 'name', 'email', 'phone', 'status', 'branch']), ['is_kyc_verified' => false]),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->isLocked()) {
            return response()->json(['message' => 'Account temporarily locked.'], 423);
        }

        if (!in_array($user->status, ['active'])) {
            return response()->json(['message' => 'Account is ' . $user->status . '.'], 403);
        }

        if ($user->two_factor_enabled) {
            $otpService = app(OtpService::class);
            $otpService->generateOtp($user);

            return response()->json([
                'message' => 'OTP required.',
                'requires_otp' => true,
                'email' => $user->email, // for the client to send back
            ], 202);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'failed_login_attempts' => 0,
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        event(new \Illuminate\Auth\Events\Login('sanctum', $user, false));

        $userData = $user->only(['id', 'name', 'email', 'phone', 'role', 'status']);
        $userData['is_kyc_verified'] = $user->isKycVerified();

        return response()->json([
            'message' => 'Login successful.',
            'user' => $userData,
            'token' => $token,
        ]);
    }

    public function verifyOtp(Request $request, OtpService $otpService)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid request.'], 400);
        }

        if (!$otpService->verifyOtp($user, $credentials['otp'])) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 401);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'failed_login_attempts' => 0,
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        event(new \Illuminate\Auth\Events\Login('sanctum', $user, false));

        $userData = $user->only(['id', 'name', 'email', 'phone', 'role', 'status']);
        $userData['is_kyc_verified'] = $user->isKycVerified();

        return response()->json([
            'message' => 'Login successful.',
            'user' => $userData,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function user(Request $request)
    {
        $userData = $request->user()->only(['id', 'name', 'email', 'phone', 'role', 'status', 'two_factor_enabled', 'last_login_at']);
        $userData['is_kyc_verified'] = $request->user()->isKycVerified();
        return response()->json([
            'user' => $userData,
        ]);
    }

    public function forgotPassword(Request $request, OtpService $otpService)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $normalizedEmail = strtolower(trim($request->email));
        $user = null;

        $branch = \App\Services\DistributedDatabaseService::findUserBranch($normalizedEmail);
        if ($branch) {
            try {
                \App\Services\DistributedDatabaseService::setActiveBranch($branch);
                $user = User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
            } catch (\Exception $e) {}
        } else {
            try {
                \App\Services\DistributedDatabaseService::setHQ();
                $user = User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
            } catch (\Exception $e) {}
        }

        if (!$user) {
            return response()->json(['message' => 'We could not find a user with that email address.'], 404);
        }

        $otp = $otpService->generateResetOtp($user);

        \App\Services\DistributedDatabaseService::setHQ();

        return response()->json([
            'message' => 'Password reset code has been generated.',
            'email' => $user->email,
            'otp' => $otp,
        ]);
    }

    public function resetPassword(Request $request, OtpService $otpService)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $normalizedEmail = strtolower(trim($validated['email']));
        $user = null;

        $branch = \App\Services\DistributedDatabaseService::findUserBranch($normalizedEmail);
        if ($branch) {
            try {
                \App\Services\DistributedDatabaseService::setActiveBranch($branch);
                $user = User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
            } catch (\Exception $e) {}
        } else {
            try {
                \App\Services\DistributedDatabaseService::setHQ();
                $user = User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
            } catch (\Exception $e) {}
        }

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (!$otpService->verifyResetOtp($user, $validated['otp'])) {
            return response()->json(['message' => 'The reset code is invalid or has expired.'], 422);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        try {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'password_reset_success',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'severity' => 'high',
            ]);
        } catch (\Exception $e) {}

        \App\Services\DistributedDatabaseService::setHQ();

        return response()->json([
            'message' => 'Your password has been reset successfully! You can now log in.',
        ]);
    }
}

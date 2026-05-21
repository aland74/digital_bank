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
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'status' => 'active',
            'role' => 'customer',
        ]);

        $accountService->createAccount($user, 'savings', 'USD', true);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully.',
            'user' => $user->only(['id', 'name', 'email', 'phone', 'status']),
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

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->only(['id', 'name', 'email', 'phone', 'role', 'status']),
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

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->only(['id', 'name', 'email', 'phone', 'role', 'status']),
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
        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'email', 'phone', 'role', 'status', 'two_factor_enabled', 'last_login_at']),
        ]);
    }
}

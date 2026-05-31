<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Services\AccountService;
use App\Services\DistributedDatabaseService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $normalizedEmail = strtolower(trim($credentials['email']));
        $hqDown = false;
        $user = null;

        // ── Step 1: Look up the user in HQ to find their branch ──
        // If HQ is down, query the branch databases sequentially.
        try {
            DistributedDatabaseService::setHQ();
            $user = User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
        } catch (\Exception $e) {
            Log::warning("AuthController@login: HQ is down! Looking up user in branch databases directly.");
            $hqDown = true;
            
            // Locate user's branch database sequentially
            $branch = DistributedDatabaseService::findUserBranch($normalizedEmail);
            if ($branch) {
                try {
                    DistributedDatabaseService::setActiveBranch($branch);
                    $user = User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
                } catch (\Exception $ex) {
                    Log::error("AuthController@login: Branch database '{$branch}' is also offline!");
                }
            }
        }

        if ($user && $user->isLocked()) {
            return back()->withErrors([
                'email' => 'Account temporarily locked. Try again after ' . $user->locked_until->diffForHumans(),
            ])->withInput($request->only('email'));
        }

        // ── Step 2: Verify Credentials ──
        if ($user && Hash::check($credentials['password'], $user->password)) {
            
            // ── Step 3: Check Email Verification ──
            if ($user->email_verified_at === null) {
                $otpService = app(OtpService::class);
                
                // Switch to branch database to cache OTP if branch is online
                if (!$user->isAdmin() && !empty($user->branch)) {
                    try {
                        $branchConn = DistributedDatabaseService::connectionForBranch($user->branch);
                        if (DistributedDatabaseService::isConnectionOnline($branchConn)) {
                            DistributedDatabaseService::setActiveBranch($user->branch);
                        }
                    } catch (\Exception $e) {}
                }

                $otpService->generateOtp($user);
                session(['pending_user_id' => $user->id]);

                return redirect()->route('auth.verify-otp')
                    ->with('warning', 'Please verify your email address to log in. A verification code has been sent.');
            }

            // ── Step 4: Attempt auth against HQ or active connection ──
            if (Auth::attempt(['email' => $user->email, 'password' => $credentials['password']], $request->boolean('remember'))) {
                $request->session()->regenerate();
                $user = Auth::user();

                // ── Step 5: Switch to the user's branch DB for all future queries ──
                if (!$user->isAdmin() && !empty($user->branch)) {
                    try {
                        $branchConn = DistributedDatabaseService::connectionForBranch($user->branch);
                        if (DistributedDatabaseService::isConnectionOnline($branchConn)) {
                            DistributedDatabaseService::setActiveBranch($user->branch);
                        } else {
                            Log::warning("Login: Branch DB '{$user->branch}' is offline. Falling back to HQ.");
                            DistributedDatabaseService::setHQFallback($user->branch);
                        }
                    } catch (\Exception $e) {
                        Log::warning("Login: Failed to route branch '{$user->branch}', staying on current connection.");
                    }
                }

                try {
                    $user->update([
                        'last_login_at' => now(),
                        'last_login_ip' => $request->ip(),
                        'failed_login_attempts' => 0,
                        'locked_until' => null,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Login: Failed to update login details on database: " . $e->getMessage());
                }

                try {
                    AuditLog::log('login', ['severity' => 'low']);
                } catch (\Exception $e) {
                    Log::warning("Login: Failed to log audit event: " . $e->getMessage());
                }

                // ── Step 6: Check 2FA for admin accounts ──
                if ($user->isAdmin() && $user->two_factor_enabled) {
                    session([
                        '2fa_user_id' => $user->id,
                        '2fa_remember' => $request->boolean('remember'),
                    ]);
                    Auth::logout();
                    return redirect()->route('auth.2fa.verify');
                }

                if ($user->isAdmin()) {
                    return redirect()->intended(route('admin.dashboard'));
                }

                return redirect()->intended(route('dashboard'));
            }
        }

        // Increment failed attempts
        if ($user) {
            try {
                $attempts = $user->failed_login_attempts + 1;
                $update = ['failed_login_attempts' => $attempts];

                if ($attempts >= 5) {
                    $update['locked_until'] = now()->addMinutes(30);
                    AuditLog::create([
                        'user_id' => $user->id,
                        'action' => 'account_locked',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'severity' => 'critical',
                    ]);
                }

                $user->update($update);
            } catch (\Exception $e) {
                Log::error("Login: Failed to update failed attempts: " . $e->getMessage());
            }
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request, AccountService $accountService, OtpService $otpService)
    {
        // ── Validate (excluding unique check because we will do a dynamic, fault-tolerant lookup) ──
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:20',
            'branch' => 'required|in:' . implode(',', DistributedDatabaseService::branchKeys()),
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'terms' => 'required|accepted',
        ]);

        $email = strtolower(trim($validated['email']));

        // Programmatic case-insensitive unique check across active databases
        $emailExists = false;
        $connectionsToCheck = DistributedDatabaseService::allConnections();

        foreach ($connectionsToCheck as $conn) {
            try {
                if (DB::connection($conn)->table('users')->whereRaw('LOWER(email) = ?', [$email])->whereNull('deleted_at')->exists()) {
                    $emailExists = true;
                    break;
                }
            } catch (\Exception $e) {
                // Ignore offline connection
            }
        }

        if ($emailExists) {
            return back()->withErrors([
                'email' => 'The email has already been taken.',
            ])->withInput($request->all());
        }

        $branch = $validated['branch'];

        // ── Step 1: Route to the BRANCH database connection ──
        try {
            $branchConn = DistributedDatabaseService::connectionForBranch($branch);
            if (DistributedDatabaseService::isConnectionOnline($branchConn)) {
                DistributedDatabaseService::setActiveBranch($branch);
            } else {
                Log::warning("Register: Branch '{$branch}' DB is offline. Routing directly to HQ fallback.");
                DistributedDatabaseService::setHQFallback($branch);
            }
        } catch (\Exception $e) {
            Log::warning("Register: Routing directly to HQ fallback.");
            DistributedDatabaseService::setHQFallback($branch);
        }

        // ── Step 2: Create User (unverified by default) ──
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'branch' => $branch,
                'city' => ucfirst($branch),
                'state' => ucfirst($branch),
                'country' => 'IQ',
                'status' => 'pending_verification', // set pending to prompt OTP verification
                'role' => 'customer',
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            Log::error("Register: Unique constraint hit during DB write for '{$email}': " . $e->getMessage());
            return back()->withErrors([
                'email' => 'The email has already been taken.',
            ])->withInput($request->all());
        } catch (\Exception $e) {
            Log::critical("Register: DB failure during user creation: " . $e->getMessage());
            return back()->withErrors([
                'email' => 'An error occurred while creating your account. Please try again.',
            ])->withInput($request->all());
        }

        // ── Step 3: Generate Verification Code ──
        $otpService->generateOtp($user);

        // Store user ID in session
        session(['pending_user_id' => $user->id]);

        return redirect()->route('auth.verify-otp')
            ->with('success', 'Your account has been registered! Please enter the 6-digit verification code sent to your email.');
    }

    public function showVerifyOtp()
    {
        $userId = session('pending_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $branch = DistributedDatabaseService::findUserBranchById($userId);
        if ($branch) {
            try {
                DistributedDatabaseService::setActiveBranch($branch);
            } catch (\Exception $e) {}
        } else {
            DistributedDatabaseService::setHQ();
        }

        $user = User::find($userId);
        if (!$user) {
            session()->forget('pending_user_id');
            return redirect()->route('login');
        }

        // In debug mode only, show OTP as flash message for developer convenience
        if (config('app.debug')) {
            $cacheKey = 'otp_' . $user->id;
            $otp = Cache::get($cacheKey);
            if ($otp) {
                session()->flash('debug_otp', $otp);
            }
        }

        return view('auth.verify-otp', compact('user'));
    }

    public function verifyOtp(Request $request, OtpService $otpService, AccountService $accountService)
    {
        $userId = session('pending_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $branch = DistributedDatabaseService::findUserBranchById($userId);
        if ($branch) {
            try {
                DistributedDatabaseService::setActiveBranch($branch);
            } catch (\Exception $e) {}
        } else {
            DistributedDatabaseService::setHQ();
        }

        $user = User::find($userId);
        if (!$user) {
            session()->forget('pending_user_id');
            return redirect()->route('login');
        }

        if ($otpService->verifyOtp($user, $request->otp)) {
            // Activate the user
            $user->update([
                'email_verified_at' => now(),
                'status' => 'active',
            ]);

            // Create both USD and IQD savings accounts
            try {
                $accountService->createAccount($user, 'savings', 'USD', true);
                $accountService->createAccount($user, 'savings', 'IQD', false);
            } catch (\Exception $e) {
                Log::error("OTP Verify: Failed to create savings account: " . $e->getMessage());
            }

            // Log the user in
            Auth::login($user);
            session()->forget('pending_user_id');

            try {
                AuditLog::log('registration', [
                    'model_type' => 'User',
                    'model_id' => $user->id,
                    'severity' => 'medium',
                ]);
            } catch (\Exception $e) {
                Log::warning("OTP Verify: Failed to log audit event: " . $e->getMessage());
            }

            // Send welcome notification with KYC prompt
            try {
                Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Welcome to Distributed Bank! 🎉',
                    'message' => 'Your account has been verified at the ' . $user->branch_display_name . ' branch. Please upload your identity documents to activate all features.',
                    'type' => 'info',
                    'icon' => '🏦',
                    'action_url' => route('profile.kyc'),
                    'data' => ['action' => 'kyc_required'],
                ]);
            } catch (\Exception $e) {
                Log::error("OTP Verify: Failed to send welcome notification: " . $e->getMessage());
            }

            return redirect()->route('dashboard')
                ->with('success', 'Email verified successfully! Welcome to Distributed Bank.');
        }

        return back()->withErrors([
            'otp' => 'The verification code is incorrect or has expired.',
        ]);
    }

    public function resendOtp(OtpService $otpService)
    {
        $userId = session('pending_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $branch = DistributedDatabaseService::findUserBranchById($userId);
        if ($branch) {
            try {
                DistributedDatabaseService::setActiveBranch($branch);
            } catch (\Exception $e) {}
        } else {
            DistributedDatabaseService::setHQ();
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login');
        }

        $otpService->generateOtp($user);

        return back()->with('success', 'A new verification code has been sent to your email address.');
    }

    public function loginWithGoogleSimulated(Request $request, AccountService $accountService, OtpService $otpService)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'branch' => 'nullable|string|in:' . implode(',', DistributedDatabaseService::branchKeys()),
        ]);

        $email = strtolower(trim($request->email));
        $name = $request->name;

        // Check if user already exists
        $user = null;
        try {
            DistributedDatabaseService::setHQ();
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        } catch (\Exception $e) {
            Log::warning("Simulated Google: HQ is down, querying branches.");
            $branch = DistributedDatabaseService::findUserBranch($email);
            if ($branch) {
                try {
                    DistributedDatabaseService::setActiveBranch($branch);
                    $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
                } catch (\Exception $ex) {
                    Log::error("Simulated Google: Failed to query branch.");
                }
            }
        }

        if ($user) {
            // User exists, log them in!
            if (!$user->isAdmin() && !empty($user->branch)) {
                try {
                    $branchConn = DistributedDatabaseService::connectionForBranch($user->branch);
                    if (DistributedDatabaseService::isConnectionOnline($branchConn)) {
                        DistributedDatabaseService::setActiveBranch($user->branch);
                    } else {
                        DistributedDatabaseService::setHQFallback($user->branch);
                    }
                } catch (\Exception $e) {}
            }

            if ($user->isLocked()) {
                return back()->withErrors(['email' => 'This account is locked.']);
            }

            Auth::login($user);

            try {
                $user->update([
                    'last_login_at' => now(),
                    'last_login_ip' => $request->ip(),
                ]);
                AuditLog::log('login', ['severity' => 'low', 'details' => 'Logged in via Google Sandbox']);
            } catch (\Exception $e) {}

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Logged in via Google Sandbox profile: ' . $user->name);
        }

        // New user! Register them instantly (requiring OTP verification).
        $branch = $request->branch;
        if (!$branch) {
            return back()->withErrors([
                'email' => 'Please select a branch location to register your account.',
            ])->withInput($request->all());
        }

        try {
            $branchConn = DistributedDatabaseService::connectionForBranch($branch);
            if (DistributedDatabaseService::isConnectionOnline($branchConn)) {
                DistributedDatabaseService::setActiveBranch($branch);
            } else {
                DistributedDatabaseService::setHQFallback($branch);
            }
        } catch (\Exception $e) {
            DistributedDatabaseService::setHQFallback($branch);
        }

        // Create the user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
            'branch' => $branch,
            'city' => ucfirst($branch),
            'state' => ucfirst($branch),
            'country' => 'IQ',
            'status' => 'pending_verification', // Require verification code entry
            'email_verified_at' => null,
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=0D8ABC&color=fff',
        ]);

        // Generate Verification Code
        $otpService->generateOtp($user);

        // Store user ID in session
        session(['pending_user_id' => $user->id]);

        try {
            AuditLog::log('registration', [
                'model_type' => 'User',
                'model_id' => $user->id,
                'severity' => 'medium',
                'details' => 'Registered via Google Sandbox (Pending verification)'
            ]);
        } catch (\Exception $e) {}

        return redirect()->route('auth.verify-otp')
            ->with('success', 'Your Google profile has been connected! Please enter the 6-digit verification code sent to your email.');
    }

    public function redirectToGoogle()
    {
        // If keys are not in env, fallback to login page
        if (!config('services.google.client_id') || !config('services.google.client_secret')) {
            return redirect()->route('login')->with('warning', 'Google credentials are not configured. Standard Sandbox mode activated.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(AccountService $accountService)
    {
        if (!config('services.google.client_id') || !config('services.google.client_secret')) {
            return redirect()->route('login')->with('warning', 'Google credentials are not configured.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::error("Google Socialite: OAuth callback failed: " . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => 'Google Authentication failed. Please try again.']);
        }

        $email = strtolower(trim($googleUser->getEmail()));

        // Check if user already exists
        $user = null;
        try {
            DistributedDatabaseService::setHQ();
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        } catch (\Exception $e) {
            $branch = DistributedDatabaseService::findUserBranch($email);
            if ($branch) {
                try {
                    DistributedDatabaseService::setActiveBranch($branch);
                    $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
                } catch (\Exception $ex) {}
            }
        }

        if ($user) {
            if (!$user->isAdmin() && !empty($user->branch)) {
                try {
                    $branchConn = DistributedDatabaseService::connectionForBranch($user->branch);
                    if (DistributedDatabaseService::isConnectionOnline($branchConn)) {
                        DistributedDatabaseService::setActiveBranch($user->branch);
                    } else {
                        DistributedDatabaseService::setHQFallback($user->branch);
                    }
                } catch (\Exception $e) {}
            }

            if ($user->isLocked()) {
                return redirect()->route('login')->withErrors(['email' => 'This account is locked.']);
            }

            Auth::login($user);

            try {
                $user->update([
                    'last_login_at' => now(),
                    'last_login_ip' => request()->ip(),
                ]);
                AuditLog::log('login', ['severity' => 'low', 'details' => 'Logged in via Google OAuth']);
            } catch (\Exception $e) {}

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Logged in via Google: ' . $user->name);
        }

        // New user - store details in session and redirect to complete profile to select branch
        session([
            'google_user_email' => $email,
            'google_user_name' => $googleUser->getName(),
            'google_user_avatar' => $googleUser->getAvatar(),
        ]);

        return redirect()->route('auth.google.complete-profile');
    }

    public function showGoogleCompleteProfile()
    {
        if (!session('google_user_email')) {
            return redirect()->route('login');
        }

        return view('auth.complete-profile');
    }

    public function storeGoogleCompleteProfile(Request $request, AccountService $accountService, OtpService $otpService)
    {
        if (!session('google_user_email')) {
            return redirect()->route('login');
        }

        $request->validate([
            'branch' => 'required|in:' . implode(',', DistributedDatabaseService::branchKeys()),
        ]);

        $email = session('google_user_email');
        $name = session('google_user_name');
        $avatar = session('google_user_avatar');
        $branch = $request->branch;

        try {
            $branchConn = DistributedDatabaseService::connectionForBranch($branch);
            if (DistributedDatabaseService::isConnectionOnline($branchConn)) {
                DistributedDatabaseService::setActiveBranch($branch);
            } else {
                DistributedDatabaseService::setHQFallback($branch);
            }
        } catch (\Exception $e) {
            DistributedDatabaseService::setHQFallback($branch);
        }

        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                'branch' => $branch,
                'city' => ucfirst($branch),
                'state' => ucfirst($branch),
                'country' => 'IQ',
                'status' => 'pending_verification', // Require verification code entry
                'email_verified_at' => null,
                'avatar' => $avatar,
            ]);
        } catch (\Exception $e) {
            Log::critical("Google Complete Profile: Failed to register user '{$email}': " . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => 'Failed to complete registration. Please try again.']);
        }

        // Generate Verification Code
        $otpService->generateOtp($user);

        // Store user ID in session
        session(['pending_user_id' => $user->id]);

        // Clear session keys
        session()->forget(['google_user_email', 'google_user_name', 'google_user_avatar']);

        try {
            AuditLog::log('registration', [
                'model_type' => 'User',
                'model_id' => $user->id,
                'severity' => 'medium',
                'details' => 'Registered via Google OAuth (Pending verification)'
            ]);
        } catch (\Exception $e) {}

        return redirect()->route('auth.verify-otp')
            ->with('success', 'Your Google profile has been connected! Please enter the 6-digit verification code sent to your email.');
    }

    // ── Two-Factor Authentication Verification ──────────────────

    public function show2faVerify()
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.verify-2fa');
    }

    public function verify2fa(Request $request)
    {
        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        // Find user in HQ
        $user = User::on(DistributedDatabaseService::getHqConnection())->find($userId);
        if (!$user) {
            // Try branches
            foreach (DistributedDatabaseService::branchDisplayNames() as $branchKey => $branchName) {
                try {
                    $connection = DistributedDatabaseService::connectionForBranch($branchKey);
                    $user = User::on($connection)->find($userId);
                    if ($user) break;
                } catch (\Exception $e) {}
            }
        }

        if (!$user || !$user->two_factor_enabled || !$user->two_factor_secret) {
            session()->forget('2fa_user_id');
            return redirect()->route('login')->withErrors(['code' => 'Invalid session. Please try again.']);
        }

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $secret = decrypt($user->two_factor_secret);

        if (!$google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Invalid verification code. Please try again.']);
        }

        // Log the user in
        Auth::login($user, session('2fa_remember', false));
        $request->session()->regenerate();
        session()->forget(['2fa_user_id', '2fa_remember']);

        // Switch to branch DB
        if (!empty($user->branch)) {
            try {
                $branchConn = DistributedDatabaseService::connectionForBranch($user->branch);
                if (DistributedDatabaseService::isConnectionOnline($branchConn)) {
                    DistributedDatabaseService::setActiveBranch($user->branch);
                }
            } catch (\Exception $e) {}
        }

        try {
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);
            AuditLog::log('login', ['severity' => 'low', 'details' => '2FA verified']);
        } catch (\Exception $e) {}

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        try {
            AuditLog::log('logout', ['severity' => 'low']);
        } catch (\Exception $e) {
            Log::warning("Logout: Failed to log audit event: " . $e->getMessage());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

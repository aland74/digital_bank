<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Routes that pending_verification users CAN access.
     * They need at minimum: dashboard, profile, KYC upload, notifications, logout.
     */
    private array $allowedRoutesForPending = [
        'dashboard',
        'profile.edit',
        'profile.update',
        'profile.security',
        'profile.update-password',
        'profile.kyc',
        'profile.kyc.upload',
        'notifications.index',
        'notifications.read',
        'notifications.mark-all-read',
        'notifications.unread-count',
        'notifications.latest',
        'logout',
    ];

    /**
     * Block access if user account is suspended or frozen.
     * Allow limited access for pending_verification users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->isLocked()) {
            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been temporarily locked. Please try again later.']);
        }

        if (in_array($user->status, ['suspended', 'frozen'])) {
            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been ' . $user->status . '. Please contact support.']);
        }

        if ($user->status === 'inactive') {
            auth()->logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is inactive. Please contact support to reactivate.']);
        }

        // pending_verification: allow only specific routes
        if ($user->status === 'pending_verification') {
            $currentRoute = $request->route()?->getName();

            if ($currentRoute && !in_array($currentRoute, $this->allowedRoutesForPending)) {
                return redirect()->route('dashboard')
                    ->with('warning', 'Please upload your identity documents to unlock all features.');
            }
        }

        return $next($request);
    }
}

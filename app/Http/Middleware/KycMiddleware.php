<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class KycMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Please login first.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        // If user status is pending_verification, they can ONLY access:
        // 1. Logout
        // 2. KYC upload/status check
        // 3. Profile viewing (read-only)

        $currentRoute = $request->route()->getName();
        $allowedRoutes = [
            'api.auth.logout',
            'api.v1.kyc.upload',
            'api.v1.kyc.status',
            'api.v1.profile.view'
        ];

        if ($user->status === 'pending_verification' && !in_array($currentRoute, $allowedRoutes)) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending KYC verification. Please upload your documents to proceed.',
                'error_code' => 'KYC_REQUIRED',
                'required_action' => 'UPLOAD_KYC'
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogger
{
    /**
     * Log all state-changing requests for audit purposes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log state-changing methods
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Don't log CSRF token refreshes or login attempts (handled separately)
            $excludedPaths = ['_token', 'login', 'logout', 'sanctum'];
            $path = $request->path();

            $shouldExclude = false;
            foreach ($excludedPaths as $excluded) {
                if (str_contains($path, $excluded)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if (!$shouldExclude && auth()->check()) {
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => $request->method() . ' ' . $request->path(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'severity' => 'low',
                    'channel' => 'web',
                    'new_values' => $this->sanitizeInput($request->except([
                        'password', 'password_confirmation', '_token', '_method',
                        'card_number', 'cvv', 'pin',
                    ])),
                ]);
            }
        }

        return $response;
    }

    private function sanitizeInput(array $input): array
    {
        $sensitive = ['secret', 'token', 'key', 'authorization'];
        foreach ($input as $key => $value) {
            foreach ($sensitive as $word) {
                if (str_contains(strtolower($key), $word)) {
                    $input[$key] = '***REDACTED***';
                }
            }
        }
        return $input;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\DistributedDatabaseService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetBranchDatabase
{
    /**
     * Handle an incoming request.
     *
     * For authenticated users: route all queries to the user's branch database.
     * For guests: keep using HQ (needed for login lookups, etc.).
     * For admins: always use HQ (admin dashboard needs the full picture).
     *
     * This middleware makes the database distribution completely transparent
     * to controllers, models, and views — they all just use the default connection.
     * It dynamically falls back to HQ if the user's branch database is down!
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Admins always operate against HQ — they need the global view
            if ($user->isAdmin()) {
                DistributedDatabaseService::setHQ();
                Log::debug("SetBranchDatabase: Admin user #{$user->id} routed to HQ");
                return $next($request);
            }

            // Regular users operate against their branch DB
            if (!empty($user->branch)) {
                try {
                    $connection = DistributedDatabaseService::connectionForBranch($user->branch);
                    
                    // Verify if connection is online
                    if (DistributedDatabaseService::isConnectionOnline($connection)) {
                        DistributedDatabaseService::setActiveBranch($user->branch);
                        Log::debug("SetBranchDatabase: Routed user #{$user->id} ({$user->email}) to branch '{$user->branch}'");
                    } else {
                        // Branch connection is down! Fall back to HQ
                        Log::warning("SetBranchDatabase: Branch '{$user->branch}' DB is offline for user #{$user->id}! Falling back to HQ.");
                        DistributedDatabaseService::setHQFallback($user->branch);
                    }
                } catch (\InvalidArgumentException $e) {
                    // Unknown branch — fall back to HQ so the user can still operate
                    Log::warning("SetBranchDatabase: Unknown branch '{$user->branch}' for user #{$user->id}, falling back to HQ");
                    DistributedDatabaseService::setHQ();
                }
            } else {
                // No branch set — use HQ
                DistributedDatabaseService::setHQ();
            }
        } else {
            // No authenticated user — use HQ (for login, register, etc.)
            DistributedDatabaseService::setHQ();
        }

        return $next($request);
    }
}

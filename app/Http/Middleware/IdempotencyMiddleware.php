<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\IdempotentRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            return $next($request);
        }

        $existingRequest = IdempotentRequest::find($idempotencyKey);

        if ($existingRequest) {
            return response()->json(
                $existingRequest->response_body,
                $existingRequest->response_code
            );
        }

        $response = $next($request);

        // Only cache successful or client-error responses, avoid 500s which might be temporary glitches
        if ($response->getStatusCode() < 500) {
            IdempotentRequest::create([
                'key' => $idempotencyKey,
                'path' => $request->path(),
                'method' => $request->method(),
                'response_code' => $response->getStatusCode(),
                'response_body' => json_decode($response->getContent(), true) ?? ['content' => $response->getContent()],
            ]);
        }

        return $response;
    }
}

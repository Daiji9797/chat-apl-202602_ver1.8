<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use App\Support\LegacyJwt;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Allow OPTIONS requests for CORS preflight
        if ($request->getMethod() === 'OPTIONS') {
            return $next($request);
        }

        $authHeader = $request->header('Authorization', '');

        if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $payload = LegacyJwt::verifyToken($matches[1]);

        if (!$payload || !isset($payload['userId'])) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $request->attributes->set('userId', (int) $payload['userId']);

        return $next($request);
    }
}

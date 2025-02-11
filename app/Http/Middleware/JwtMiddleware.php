<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }

            // Verify and authenticate the token
            $user = JWTAuth::parseToken()->authenticate();

            return $next($request);

        } catch (JWTException $e) {
            return response()->json(['error' => 'Token not valid'], 401);
        }
    }
}

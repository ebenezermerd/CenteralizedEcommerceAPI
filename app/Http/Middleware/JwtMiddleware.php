<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\MFATokenService;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class JwtMiddleware
{
    protected $mfaEndpoints = [
        'auth/mfa/setup',
        'auth/mfa/verify',
        'auth/mfa/status',
        'auth/mfa/download-qr',
        'auth/mfa/regenerate-backup-codes'
    ];

    public function handle($request, Closure $next) 
    {
        try {
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }

            // Check if current path is MFA-related
            $currentPath = $request->path();
            $isMfaEndpoint = false;
            foreach ($this->mfaEndpoints as $endpoint) {
                if (strpos($currentPath, $endpoint) !== false) {
                    $isMfaEndpoint = true;
                    break;
                }
            }

            if ($isMfaEndpoint) {
                // Validate temporary token for MFA endpoints
                $userId = app(MFATokenService::class)->validateTempToken($token);
                if (!$userId) {
                    Log::warning('Invalid MFA temporary token attempt', [
                        'path' => $currentPath,
                        'ip' => $request->ip(),
                        'userId' => $userId,
                        'token_provided' => $token,
                        'request_parameters' => $request, // Exclude sensitive data
                        'referrer' => $request->header('referer'),
                        ]);
                    return response()->json(['error' => 'Invalid temporary token from the middleware'], 401);
                }
                // Set the authenticated user context
                $user = User::find($userId);
                Auth::login($user);
                return $next($request);
            }
            
            // Normal JWT validation
            $user = JWTAuth::parseToken()->authenticate();
            return $next($request);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token not valid'], 401);
        }
    }
}
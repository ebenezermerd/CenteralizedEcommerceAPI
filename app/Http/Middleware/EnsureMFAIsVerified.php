<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureMFAIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->is_mfa_enabled && !$user->mfa_verified_at) {
            return response()->json([
                'message' => 'MFA verification required',
                'mfaRequired' => true
            ], 403);
        }

        return $next($request);
    }
}

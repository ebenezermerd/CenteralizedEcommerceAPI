<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyCsrfToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('delete')) {
            $token = $request->header('X-CSRF-TOKEN') ?? $request->input('_token');

            if (!$token || $token !== csrf_token()) {
                return response()->json(['message' => 'CSRF token mismatch'], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
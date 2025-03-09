<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Log;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $messages = [
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'email.exists' => 'No account found with this email address',
            'redirectTo.url' => 'Invalid redirect URL format',
            'reset_url.url' => 'Invalid reset URL format'
        ];

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'redirectTo' => 'sometimes|url',
            'reset_url' => 'sometimes|url'
        ], $messages);

        try {
            if (RateLimiter::tooManyAttempts('forgot-password:' . $request->ip(), 3)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many password reset attempts. Please try again later.',
                    'seconds_remaining' => RateLimiter::availableIn('forgot-password:' . $request->ip())
                ], 429);
            }

            // Find the user and create a JWT token with their ID
            $user = User::where('email', $request->email)->first();
            $resetToken = Password::createToken($user);

            // Create JWT token with user ID and reset token
            $jwtToken = JWTAuth::claims([
                'user_id' => $user->id,
                'reset_token' => $resetToken,
                'exp' => now()->addMinutes(60)->timestamp
            ])->fromUser($user);

            try {
                $user->notify(new ResetPasswordNotification(
                    $jwtToken,
                    $request->redirectTo,
                    $request->reset_url
                ));

                RateLimiter::hit('forgot-password:' . $request->ip());
                return response()->json([
                    'status' => 'success',
                    'message' => 'Password reset link has been sent to your email'
                ], 200);
            } catch (Exception $e) {
                Log::error('Failed to send password reset email', [
                    'error' => $e->getMessage(),
                    'email' => $request->email
                ]);
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('Password reset error', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send password reset email. Please try again later.'
            ], 500);
        }
    }
}

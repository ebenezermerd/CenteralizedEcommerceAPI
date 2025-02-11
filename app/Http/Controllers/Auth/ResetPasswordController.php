<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRules;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Auth\Events\PasswordReset;

class ResetPasswordController extends Controller
{
    public function reset(Request $request)
    {
        try {
            // Decode the JWT token
            $payload = JWTAuth::setToken($request->token)->getPayload();
            $userId = $payload->get('user_id');
            $resetToken = $payload->get('reset_token');

            $user = User::findOrFail($userId);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            $request->merge([
                'token' => $resetToken,
            ]);

            $messages = [
                'token.required' => 'Reset token is required',
                'token.string' => 'Invalid reset token format',
                'password.required' => 'Password is required',
                'password.confirmed' => 'Password confirmation does not match',
                'password.min' => 'Password must be at least 8 characters',
                'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number and one special character'
            ];

            $request->validate([
                'token' => 'required|string',
                'password' => [
                    'required',
                    'confirmed',
                    'min:8',
                    'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
                ],
                'redirectTo' => 'sometimes|url'
            ], $messages);

            $status = Password::reset(
                array_merge(
                    $request->only('password', 'password_confirmation', 'token'),
                    ['email' => $user->email]
                ),
                function ($user) use ($request, $userId) {
                    // Ensure we're using the correct user from earlier
                    $user = User::findOrFail($userId);

                    $user->forceFill([
                        'password' => bcrypt($request->password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    // Log password reset attempt
                    Log::info('Password reset attempt', [
                        'user_id' => $user->getKey(),
                        'email' => $user->getAttribute('email'),
                        'status' => 'success',
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent()
                    ]);

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Password has been reset successfully',
                    'redirect_url' => $request->redirectTo ?? config('app.frontend_url') . '/auth/jwt/sign-in'
                ], 200);
            }

            // Log failed password reset attempt
            Log::warning('Password reset failed', [
                'status' => $status,
                'ip' => $request->ip(),
                'user_id' => $user->getKey(),
                'status result' => $status,
                'user_agent' => $request->userAgent(),
                'message' => __($status)
            ]);

            return response()->json([
                'status' => 'error',
                'message' => __($status)
            ], 400);
        } catch (JWTException $e) {
            Log::error('Invalid JWT token during reset', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid reset token'
            ], 400);
        }
    }

    public function checkToken(Request $request, $token)
    {
        try {
            // Decode the JWT token
            $payload = JWTAuth::setToken($token)->getPayload();
            $userId = $payload->get('user_id');
            $resetToken = $payload->get('reset_token');

            $user = User::findOrFail($userId);

            // Check if token exists and is valid
            $tokenValid = Password::tokenExists($user, $resetToken);

            Log::info('Password reset token check', [
                'user_id' => $userId,
                'token_valid' => $tokenValid,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'status' => $tokenValid ? 'success' : 'error',
                'valid' => $tokenValid,
                'message' => $tokenValid ? 'Token is valid' : 'Token is invalid or has expired',
                'redirectTo' => $request->query('redirectTo')
            ]);
        } catch (JWTException $e) {
            Log::error('Invalid JWT token', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid reset token'
            ], 400);
        }
    }
}

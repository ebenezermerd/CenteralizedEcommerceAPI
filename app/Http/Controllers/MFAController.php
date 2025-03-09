<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\EmailVerificationService;
use App\Http\Resources\UserResource;
use Tymon\JWTAuth\Facades\JWTAuth;

class MFAController extends Controller
{
    protected $emailVerificationService;

    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
    }

    public function enable(Request $request)
    {
        $user = Auth::user();
        $user->is_mfa_enabled = true;
        $user->save();

        // $this->emailVerificationService->sendMfaOtp($user);

        return response()->json(['message' => 'MFA enabled successfully', 'status' => 'success', 'type' => 'success'], 201);
    }

    public function disable(Request $request)
    {
        $user = Auth::user();
        $user->is_mfa_enabled = false;
        $user->mfa_verified_at = null;
        $user->save();

        return response()->json(['message' => 'MFA disabled', 'status' => 'success'], 201);
    }

    public function getStatus(Request $request)
    {
        $user = Auth::user();
        return response()->json([
            'is_mfa_enabled' => $user->is_mfa_enabled,
            'mfa_verified_at' => $user->mfa_verified_at,
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6'
        ]);

        $user = Auth::user();

        if ($this->emailVerificationService->verifyMfaOtp($user->email, $request->otp)) {
            $user->mfa_verified_at = now();
            $user->save();

            // Generate access token with 15 minutes TTL
            JWTAuth::factory()->setTTL(15);
            $token = JWTAuth::fromUser($user);

            // Generate refresh token with 1 week TTL
            JWTAuth::factory()->setTTL(10080);
            $refreshToken = JWTAuth::fromUser($user, ['refresh' => true]);

            return response()->json([
                'status' => 'success',
                'accessToken' => $token,
                'refreshToken' => $refreshToken,
                'user' => new UserResource($user),
                'role' => $user->getRoleNames()->first(),
                'mfaRequired' => $user->is_mfa_enabled,
                'expires_in' => 15 * 60,
                'refresh_expires_in' => 10080 * 60
            ], 200);
        }

        return response()->json(['error' => 'Invalid OTP'], 400);
    }

    public function resendOtp(Request $request)
    {
        $user = Auth::user();
        $this->emailVerificationService->sendMfaOtp($user);

        return response()->json(['message' => 'OTP resent successfully']);
    }
}

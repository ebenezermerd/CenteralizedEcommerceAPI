<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    protected $emailVerificationService;

    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
    }

    public function sendVerificationOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();

        try {
            $this->emailVerificationService->sendVerificationEmail($user);
            return response()->json(['message' => 'OTP sent successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to send verification OTP', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);
            return response()->json(['error' => 'Failed to send OTP'], 500);
        }
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6'
        ]);

        if ($this->emailVerificationService->verifyOTP($request->email, $request->otp)) {
            $user = User::where('email', $request->email)->first();
            $user->email_verified_at = now();
            $user->verified = true;
            $user->save();

            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json(['message' => 'Email verified successfully']);
        }

        return response()->json(['error' => 'Invalid OTP'], 400);
    }


    public function resendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->verified) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        try {
            $this->emailVerificationService->sendVerificationEmail($user);
            return response()->json(['message' => 'Verification email resent successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);
            return response()->json(['error' => 'Failed to resend verification email'], 500);
        }
    }
}

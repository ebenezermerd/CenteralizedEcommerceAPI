<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\SendOtpMail;

class EmailVerificationService
{
    public function generateOTP(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function sendVerificationEmail(User $user)
    {
        $otp = $this->generateOTP();

        // Store OTP in cache for 10 minutes
        Cache::put('email_verify_' . $user->email, $otp, now()->addMinutes(10));

        // Use the SendOtpMail Mailable class
        Mail::to($user->email)->send(new SendOtpMail($otp));

        return true;
    }

    public function verifyOTP(string $email, string $otp): bool
    {
        $cachedOTP = Cache::get('email_verify_' . $email);
        return $cachedOTP === $otp;
    }

    public function sendMfaOtp(User $user)
    {
        $otp = $this->generateOTP();

        // Store OTP in cache for 10 minutes
        Cache::put('mfa_otp_' . $user->email, $otp, now()->addMinutes(10));

        // Use the SendOtpMail Mailable class
        Mail::to($user->email)->send(new SendOtpMail($otp));

        return true;
    }

    public function verifyMfaOtp(string $email, string $otp): bool
    {
        $cachedOTP = Cache::get('mfa_otp_' . $email);
        return $cachedOTP === $otp;
    }
}

<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MfaOtpMail extends Mailable
{
    use SerializesModels;

    public function __construct(private string $otp)
    {
    }

    public function build()
    {
        return $this->subject('Multi-Factor Authentication: Security Verification Code')
            ->view('emails.mfa-otp')
            ->with(['otp' => $this->otp]);
    }
}

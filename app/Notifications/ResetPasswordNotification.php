<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;
    public $redirectTo;
    public $resetUrl;

    public function __construct($token, $redirectTo = null, $resetUrl = null)
    {
        $this->token = $token;
        $this->redirectTo = $redirectTo;
        $this->resetUrl = $resetUrl;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expireInMinutes = config('auth.passwords.users.expire', 60);

        // Build the reset URL
        // $baseUrl = $this->redirectTo . '/auth/jwt/update-password';
        $baseUrl = $this->resetUrl;
        // The token is already a JWT token containing user_id and reset_token
        $url = $baseUrl . '?token=' . $this->token;

        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $url)
            ->line("This password reset link will expire in {$expireInMinutes} minutes.")
            ->line('If you did not request a password reset, no further action is required.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserAccountDeleted extends Mailable
{
    use SerializesModels;

    public function __construct(private User $user) {}

    public function build()
    {
        return $this->subject('Your Account Has Been Removed')
            ->view('emails.user-account-deleted')
            ->with([
                'user' => $this->user
            ]);
    }
}

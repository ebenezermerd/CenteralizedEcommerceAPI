<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRoleUpdated extends Mailable
{
    use SerializesModels;

    public function __construct(
        private User $user,
        private string $oldRole,
        private string $newRole
    ) {}

    public function build()
    {
        return $this->subject('Your Account Role Has Been Updated')
            ->view('emails.user-role-updated')
            ->with([
                'user' => $this->user,
                'oldRole' => $this->oldRole,
                'newRole' => $this->newRole
            ]);
    }
}

<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationEmail extends Mailable
{
    use SerializesModels;

    public function __construct(private User $user)
    {
    }

    public function build()
    {
        // Eager load company relationship with all its attributes
        $this->user->load(['company' => function($query) {
            $query->withTrashed(); // In case the company is soft deleted
        }]);

        return $this->subject($this->getSubject())
            ->view('common.registration_successful')
            ->with([
                'user' => $this->user,
                'isSupplier' => $this->user->getRoleNames()->first() === 'supplier'
            ]);
    }

    private function getSubject(): string
    {
        if ($this->user->getRoleNames()->first() === 'supplier') {
            return 'Supplier Registration Successful - Pending Approval';
        }
        return 'Registration Successful';
    }
}


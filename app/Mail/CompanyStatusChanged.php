<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyStatusChanged extends Mailable
{
    use SerializesModels;

    public function __construct(private Company $company, private string $previousStatus) {}

    public function build()
    {
        $this->company->load('owner');

        return $this->subject($this->getSubject())
            ->view('emails.company-status-changed')
            ->with([
                'company' => $this->company,
                'previousStatus' => $this->previousStatus,
                'newStatus' => $this->company->status
            ]);
    }

    private function getSubject(): string
    {
        return match ($this->company->status) {
            'active' => 'Company Registration Approved - Welcome to Korecha!',
            'inactive' => 'Company Account Deactivated',
            'blocked' => 'Company Account Suspended',
            default => 'Company Status Update'
        };
    }
}

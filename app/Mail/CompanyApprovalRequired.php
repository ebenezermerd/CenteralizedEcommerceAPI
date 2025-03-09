<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyApprovalRequired extends Mailable
{
    use SerializesModels;

    public function __construct(private Company $company) {}

    public function build()
    {
        return $this->subject('Company Approval Required - Product Creation Restricted')
            ->view('emails.company-approval-required')
            ->with([
                'company' => $this->company
            ]);
    }
}

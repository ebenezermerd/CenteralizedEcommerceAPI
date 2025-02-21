<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyUpdated extends Mailable
{
    use SerializesModels;

    public function __construct(private Company $company, private array $changedFields) {}

    public function build()
    {
        $this->company->load('owner');

        return $this->subject('Company Information Updated')
            ->view('emails.company-updated')
            ->with([
                'company' => $this->company,
                'changedFields' => $this->changedFields
            ]);
    }
}

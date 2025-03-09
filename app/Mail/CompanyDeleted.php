<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyDeleted extends Mailable
{
    use SerializesModels;

    public function __construct(private Company $company) {}

    public function build()
    {
        $this->company->load('owner');

        return $this->subject('Company Account Removed')
            ->view('emails.company-deleted')
            ->with([
                'company' => $this->company
            ]);
    }
}

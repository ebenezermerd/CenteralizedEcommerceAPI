<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Invoice;

class InvoiceCreated extends Mailable
{
    use SerializesModels;

    public $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function build()
    {
        return $this->subject('Invoice Created')
            ->view('emails.invoice-created')
            ->with([
                'invoice' => $this->invoice
            ]);
    }
}


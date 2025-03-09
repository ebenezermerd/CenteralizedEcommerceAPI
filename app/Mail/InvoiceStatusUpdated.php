<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceStatusUpdated extends Mailable
{
    use SerializesModels;

    public function __construct(
        private Invoice $invoice,
        private string $previousStatus
    ) {}

    public function build()
    {
        return $this->subject('Invoice Status Updated - #' . $this->invoice->invoice_number)
            ->view('emails.invoice-status-updated')
            ->with([
                'invoice' => $this->invoice,
                'previousStatus' => $this->previousStatus
            ]);
    }
}

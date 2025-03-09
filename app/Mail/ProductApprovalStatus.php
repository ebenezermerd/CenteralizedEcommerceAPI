<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductApprovalStatus extends Mailable
{
    use Queueable, SerializesModels;

    public $product;
    public $status;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(Product $product, string $status, ?string $reason = null)
    {
        $this->product = $product;
        $this->status = $status;
        $this->reason = $reason;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->status === 'approved'
            ? 'Product Approved: ' . $this->product->name
            : 'Product Requires Changes: ' . $this->product->name;

        return $this->subject($subject)
                   ->view('emails.product-approval-status');
    }
}

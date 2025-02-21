<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class LowStockAlert extends Mailable
{
    use SerializesModels;

    public function __construct(
        private Collection $lowStockProducts,
        private int $threshold
    ) {}

    public function build()
    {
        return $this->subject('Low Stock Alert - Action Required')
            ->view('emails.low-stock-alert')
            ->with([
                'products' => $this->lowStockProducts,
                'threshold' => $this->threshold
            ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'payment_method', 'amount', 'currency', 'tx_ref', 'status', 'bank_account', 'transaction_id', 'payment_date', 'original_tx_ref'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

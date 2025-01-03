<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'payment_method', 'bank_account', 'mobile_banking_number', 'proof_of_payment', 'status'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

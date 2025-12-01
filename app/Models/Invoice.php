<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'sent',
        'taxes',
        'status',
        'subtotal',
        'discount',
        'shipping',
        'total_amount',
        'invoice_number',
        'create_date',
        'due_date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function billFrom()
    {
        return $this->hasOne(InvoiceFrom::class);
    }

    public function billTo()
    {
        return $this->hasOne(InvoiceTo::class);
    }
}

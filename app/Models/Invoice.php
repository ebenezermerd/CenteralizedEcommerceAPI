<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'sent', 'taxes', 'status', 'subtotal', 'discount', 'shipping', 'total_amount', 'invoice_number', 'create_date', 'due_date'
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}

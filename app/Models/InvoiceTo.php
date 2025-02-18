<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceTo extends Model
{
    use HasFactory;

    protected $table = 'invoice_to';

    protected $fillable = [
        'invoice_id',
        'name',
        'full_address',
        'phone_number',
        'email'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}

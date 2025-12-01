<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'payment_time', 'delivery_date', 'completion_time', 'timeline'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

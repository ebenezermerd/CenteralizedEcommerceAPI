<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
    'user_id',
    'taxes',
    'status',
    'quantity',
    'shipping',
    'discount',
    'subtotal',
    'order_number',
    'total_amount',
    'total_quantity',
];
}

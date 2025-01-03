<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProductItem extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'product_id', 'price', 'quantity'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateSubtotal()
    {
        $this->subtotal = $this->quantity * $this->price;
        $this->save();
    }
}

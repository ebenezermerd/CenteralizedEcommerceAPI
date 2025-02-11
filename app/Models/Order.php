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
        'shipping',
        'discount',
        'subtotal',
        'order_number',
        'total_amount',
        'total_quantity'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderProductItem::class);
    }

    public function history()
    {
        return $this->hasOne(OrderHistory::class);
    }

    public function payment()
    {
        return $this->hasOne(OrderPayment::class);
    }

    public function shippingAdd()
    {
        return $this->hasOne(OrderShipping::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function customer()
    {
        return $this->hasOne(OrderCustomer::class);
    }

    public function delivery()
    {
        return $this->hasOne(OrderDelivery::class);
    }

    public function productItems()
    {
        return $this->hasMany(OrderProductItem::class);
    }


    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum(fn ($item) => $item->subtotal);
        $this->taxes = $this->subtotal * 0.1; // Example tax calculation
        $this->total_amount = $this->subtotal + $this->taxes - $this->discount + $this->shipping;
        $this->save();
    }
}

<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProductItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'price',
        'quantity',
        'additional_cost',
        'name',
        'sku',
        'cover_url'
    ];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'additional_cost' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getSubtotalAttribute()
    {
        return ($this->quantity * $this->price) + $this->additional_cost;
    }

    public function calculateSubtotal()
    {
        $this->order->calculateTotals();
    }
    
    /**
     * Get whether this item has additional costs
     *
     * @return bool
     */
    public function hasAdditionalCost(): bool
    {
        return $this->additional_cost > 0;
    }
}

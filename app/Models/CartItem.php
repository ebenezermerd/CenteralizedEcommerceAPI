<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = ['cart_id', 'product_id', 'quantity', 'price', 'subtotal', 'additional_cost'];

    protected $casts = [
        'price' => 'float',
        'subtotal' => 'float',
        'additional_cost' => 'float',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateSubtotal()
    {
        $product = $this->product;
        $additionalCost = 0;
        
        if ($product && $product->hasAdditionalCost($this->quantity)) {
            $additionalCost = $product->calculateAdditionalCost($this->quantity);
        }
        
        $this->additional_cost = $additionalCost;
        $this->subtotal = ($this->quantity * $this->price) + $additionalCost;
        $this->save();
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

<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use Illuminate\Database\Eloquent\Model;


class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'session_id', 'subtotal', 'shipping', 'discount', 'tax', 'total', 'additional_costs_total'];

    protected $casts = [
        'subtotal' => 'float',
        'shipping' => 'float',
        'discount' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'additional_costs_total' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Calculate the cart totals
     */
    public function calculateTotals()
    {
        // Base subtotal without additional costs
        $baseSubtotal = $this->items->sum(function($item) {
            return $item->price * $item->quantity;
        });
        
        // Total of additional costs
        $additionalCostsTotal = $this->items->sum('additional_cost');
        
        $this->additional_costs_total = $additionalCostsTotal;
        $this->subtotal = $baseSubtotal + $additionalCostsTotal;
        $this->tax = $this->subtotal * 0.15; // 15% tax
        $this->total = $this->subtotal + $this->tax - $this->discount + $this->shipping;
        $this->save();
    }
}

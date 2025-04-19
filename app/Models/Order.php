<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordsEcommerceAnalytics;

class Order extends Model
{
    use HasFactory, RecordsEcommerceAnalytics;

    protected $fillable = [
        'user_id',
        'taxes',
        'status',
        'shipping',
        'discount',
        'subtotal',
        'additional_costs_total',
        'order_number',
        'total_amount',
        'total_quantity'
    ];

    protected $casts = [
        'subtotal' => 'float',
        'taxes' => 'float',
        'shipping' => 'float',
        'discount' => 'float',
        'total_amount' => 'float',
        'additional_costs_total' => 'float',
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
        // Base price * quantity for all items
        $baseSubtotal = $this->items->sum(function($item) {
            return $item->quantity * $item->price;
        });
        
        // Total of additional costs
        $additionalCostsTotal = $this->items->sum('additional_cost');
        
        $this->additional_costs_total = $additionalCostsTotal;
        $this->subtotal = $baseSubtotal + $additionalCostsTotal;
        $this->taxes = $this->subtotal * 0.15; // 15% tax
        $this->total_amount = $this->subtotal + $this->taxes + $this->shipping - $this->discount;
        $this->save();
    }

    protected static function booted()
    {
        static::created(function ($order) {
            $order->recordAnalytics('sale', $order->total_amount, [
                'order_id' => $order->id,
                'vendor_id' => $order->items->first()->product->vendor_id ?? null
            ]);
        });

        static::updated(function ($order) {
            if ($order->status === 'completed' && $order->getOriginal('status') !== 'completed') {
                $order->recordAnalytics('revenue', $order->total_amount, [
                    'order_id' => $order->id,
                    'vendor_id' => $order->items->first()->product->vendor_id ?? null
                ]);
            }
        });
    }
}

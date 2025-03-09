<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReservation extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'session_id',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function cleanupExpired(): void
    {
        self::where('expires_at', '<=', now())
            ->chunk(100, function ($reservations) {
                foreach ($reservations as $reservation) {
                    // Return quantity to product
                    $reservation->product->increment('available', $reservation->quantity);
                    $reservation->delete();
                }
            });
    }
}

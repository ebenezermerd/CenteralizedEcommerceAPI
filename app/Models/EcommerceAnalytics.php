<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EcommerceAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'count',
        'metadata',
        'recorded_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'recorded_at' => 'datetime',
        'amount' => 'decimal:2'
    ];
}

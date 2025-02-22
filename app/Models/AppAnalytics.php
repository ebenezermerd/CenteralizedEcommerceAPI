<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'platform',
        'count',
        'metadata',
        'recorded_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'recorded_at' => 'datetime',
        'count' => 'integer'
    ];
}

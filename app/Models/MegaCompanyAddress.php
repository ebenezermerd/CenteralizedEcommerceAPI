<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MegaCompanyAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'full_address',
        'phone_number',
        'email',
        'type',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // When creating a new address and it's set as default
        static::creating(function ($address) {
            if ($address->is_default) {
                static::where('is_default', true)->update(['is_default' => false]);
            }
        });

        // When updating an address to be default
        static::updating(function ($address) {
            if ($address->isDirty('is_default') && $address->is_default) {
                static::where('is_default', true)->update(['is_default' => false]);
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

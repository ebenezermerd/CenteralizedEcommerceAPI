<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_PENDING = 'pending';
    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'name',
        'description',
        'email',
        'phone',
        'country',
        'city',
        'address',
        'agreement',
        'status',
        'owner_id'
    ];

    protected $casts = [
        'agreement' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'string'
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    // Custom pagination configuration
    public const PAGINATION_LIMIT = 10;

    // Relationship with owner (User)
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Relationship with company users
    public function users()
    {
        return $this->hasMany(User::class);
    }
}

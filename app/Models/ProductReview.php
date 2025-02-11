<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductReview extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'name',
        'rating',
        'comment',
        'helpful',
        'avatar_url',
        'posted_at',
        'is_purchased',
        'attachments'
    ];

    protected $casts = [
        'rating' => 'float',
        'helpful' => 'integer',
        'is_purchased' => 'boolean',
        'attachments' => 'array',
        'posted_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

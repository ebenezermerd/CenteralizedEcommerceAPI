<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $table = 'product_reviews';

    protected $fillable = [
        'comment',
        'name',
        'product_id',
        'rating',
        'user_id',
        'helpful',
        'avatar_url',
        'posted_at',
        'is_purchased'
    ];

    public $incrementing = false; // Disable auto-incrementing
    protected $keyType = 'string'; // Set the key type to string

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

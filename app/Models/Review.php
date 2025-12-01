<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'name', 'rating', 'comment', 'helpful', 'avatar_url', 'posted_at', 'is_purchased'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

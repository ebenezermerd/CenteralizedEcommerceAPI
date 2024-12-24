<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'product_id' => 'string'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute()
    {
        return Storage::url($this->image_path);
    }
}

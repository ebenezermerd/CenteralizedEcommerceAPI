<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'code',
        'price',
        'taxes',
        'tags',
        'sizes',
        'publish',
        'gender',
        'cover_url',
        'images',
        'colors',
        'quantity',
        'category',
        'available',
        'total_sold',
        'description',
        'total_ratings',
        'total_reviews',
        'inventory_type',
        'sub_description',
        'price_sale',
        'sale_label',
        'new_label',
    ];

    protected $casts = [
        'tags' => 'array',
        'sizes' => 'array',
        'gender' => 'array',
        'images' => 'array',
        'colors' => 'array',
        'sale_label' => 'array',
        'new_label' => 'array',
        'price' => 'decimal:2',
        'taxes' => 'decimal:2',
        'price_sale' => 'decimal:2',
    ];
}

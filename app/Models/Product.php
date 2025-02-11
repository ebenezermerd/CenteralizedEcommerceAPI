<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    protected $fillable = [
        'categoryId',
        'name',
        'sku',
        'code',
        'description',
        'subDescription',
        'publish',
        'price',
        'priceSale',
        'taxes',
        'coverUrl',
        'tags',
        'sizes',
        'colors',
        'gender',
        'inventoryType',
        'quantity',
        'available',
        'totalSold',
        'totalRatings',
        'totalReviews',
        'newLabel',
        'saleLabel',
        'vendor_id'
    ];

    protected $casts = [
        'id' => 'string',
        'categoryId' => 'integer',
        'price' => 'decimal:2',
        'priceSale' => 'decimal:2',
        'taxes' => 'decimal:2',
        'tags' => 'array',
        'sizes' => 'array',
        'colors' => 'array',
        'gender' => 'array',
        'quantity' => 'integer',
        'available' => 'integer',
        'totalSold' => 'integer',
        'totalRatings' => 'float',
        'totalReviews' => 'integer',
        'newLabel' => 'array',
        'saleLabel' => 'array'
    ];

    protected $attributes = [
        'publish' => 'draft',
        'inventoryType' => 'in_stock',
        'quantity' => 0,
        'available' => 0,
        'totalSold' => 0,
        'totalRatings' => 0,
        'totalReviews' => 0,
        'taxes' => 0,
        'coverUrl' => 'products/default-cover.png' // Add default cover URL
    ];

    protected $with = ['category', 'images']; // eager load relationships by default

    protected $withCount = ['reviews']; // always count reviews

    public function category()
    {
        return $this->belongsTo(Category::class, 'categoryId');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('is_primary', 'desc');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function getCoverImageAttribute()
    {
        return $this->images()->where('is_primary', true)->first()?->image_path;
    }

    // For frequently accessed products, use caching:
    public function getTotalReviewsAttribute()
    {
        return cache()->remember("product.{$this->id}.reviews_count", 3600, function () {
            return $this->reviews()->count();
        });
    }

    public function getAverageRatingAttribute()
    {
        return cache()->remember("product.{$this->id}.avg_rating", 3600, function () {
            return $this->reviews()->avg('rating') ?? 0;
        });
    }

    // Add accessor for inventory status
    public function getInventoryStatusAttribute(): string
    {
        if ($this->quantity <= 0) {
            return 'out_of_stock';
        }
        if ($this->quantity <= 10) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    // Add accessor for price with taxes
    public function getPriceWithTaxAttribute(): float
    {
        return $this->price * (1 + ($this->taxes / 100));
    }

    // Add scope for published products
    public function scopePublished($query)
    {
        return $query->where('publish', 'published');
    }

    // Scope for getting vendor's products
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    // Scope for getting products viewable by user
    public function scopeViewableBy($query, User $user)
    {
        if ($user->hasRole('admin')) {
            return $query; // Admin can see all products
        }

        if ($user->hasRole('supplier')) {
            return $query->where('vendor_id', $user->id);
        }

        return $query->where('publish', 'published'); // Customers see only published products
    }

    public function orderItems()
    {
        return $this->hasMany(OrderProductItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}

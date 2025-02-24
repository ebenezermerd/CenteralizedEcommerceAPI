<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'logo'
    ];

    /**
     * The categories that belong to the brand.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'brand_category');
    }
}
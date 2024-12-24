<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parentId',
        'group',
        'description',
        'slug',
        'isActive'
    ];

    protected $casts = [
        'isActive' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parentId');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parentId');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'categoryId');
    }

    public function scopeMainCategories($query)
    {
        return $query->whereNull('parentId');
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public static function findByName($name)
    {
        return static::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
    }

    public static function findByNameStrict($name)
    {
        return static::where('name', $name)->first();
    }
}

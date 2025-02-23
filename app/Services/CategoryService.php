<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\CategoryNotFoundException;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    private const CACHE_TTL = 3600; // 1 hour
    
    public function getAllCategories(): Collection
    {
        return Cache::remember('categories.all', self::CACHE_TTL, function () {
            return Category::with('children')->mainCategories()->get();
        });
    }
    
    public function findCategoryBySlug(string $slug): Category
    {
        return Cache::remember("categories.slug.{$slug}", self::CACHE_TTL, function () use ($slug) {
            return Category::where('slug', $slug)
                          ->with(['parent', 'children'])
                          ->firstOrFail();
        });
    }
    
    public function createCategory(array $data): Category
    {
        $category = Category::create($data);
        $this->clearCategoryCache();
        return $category;
    }
    
    private function clearCategoryCache(): void
    {
        Cache::tags(['categories'])->flush();
    }
}

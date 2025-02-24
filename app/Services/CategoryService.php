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

    public function getCategoryStructure(): array
    {
        return Cache::remember('categories.structure', self::CACHE_TTL, function () {
            return Category::with('children')
                ->mainCategories()
                ->get()
                ->map(function ($category) {
                    return [
                        'group' => $category->name,
                        'classify' => $category->children->pluck('name')->toArray()
                    ];
                })
                ->toArray();
        });
    }

    public function validateCategory(string $categoryName): bool
    {
        return Cache::remember("category.validate.{$categoryName}", 300, function () use ($categoryName) {
            return Category::where('name', $categoryName)->exists();
        });
    }

    public function getCategoryBrands(string $categoryName): array
    {
        return Cache::remember("category.brands.{$categoryName}", self::CACHE_TTL, function () use ($categoryName) {
            // You might want to implement your own brand logic here
            // This is just an example based on your current structure
            $brands = config("brands.{$categoryName}", []);
            return array_map(function ($brand) {
                return [
                    'name' => $brand['name'],
                    'description' => $brand['description']
                ];
            }, $brands);
        });
    }
}

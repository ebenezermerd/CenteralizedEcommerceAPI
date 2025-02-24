<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductFilterController extends Controller
{
    /**
     * Filter products based on multiple criteria
     */
    public function filter(Request $request)
    {
        try {
            $query = Product::query()
                ->with(['reviews', 'category', 'brand', 'images', 'vendor'])
                ->published();

            // Filter by category ID
            if ($request->has('categoryId')) {
                $query->where('categoryId', $request->categoryId);
            }

            // Filter by brand IDs
            if ($request->has('brandIds')) {
                $brandIds = json_decode($request->brandIds);
                if (!empty($brandIds)) {
                    $query->whereIn('brandId', $brandIds);
                }
            }

            // Apply price range filter
            if ($request->has('priceRange')) {
                $range = json_decode($request->priceRange, true);
                if (isset($range['start']) && $range['start'] > 0) {
                    $query->where('price', '>=', $range['start']);
                }
                if (isset($range['end']) && $range['end'] > 0) {
                    $query->where('price', '<=', $range['end']);
                }
            }

            // Apply color filter
            if ($request->has('colors') && !empty($request->colors)) {
                $query->where(function($q) use ($request) {
                    foreach ($request->colors as $color) {
                        $q->orWhereJsonContains('colors', $color);
                    }
                });
            }

            // Apply gender filter
            if ($request->has('gender') && !empty($request->gender)) {
                $query->whereJsonContains('gender', $request->gender);
            }

            // Include review stats
            $query->withCount('reviews')
                  ->withAvg('reviews', 'rating');

            // Apply sorting
            $sort = $request->input('sort', 'latest');
            switch ($sort) {
                case 'oldest':
                    $query->oldest();
                    break;
                case 'popular':
                    $query->orderByDesc('reviews_count');
                    break;
                default:
                    $query->latest();
            }

            $products = $query->paginate(12);

            return response()->json([
                'products' => ProductResource::collection($products),
                'meta' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Product filtering failed', [
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'error' => 'Failed to filter products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available categories with their brands
     */
    public function getCategories()
    {
        try {
            $categories = Category::with('brands:id,name')
                ->main
                ->get(['id', 'name', 'coverImg'])
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'coverImg' => $category->coverImg,
                        'brands' => $category->brands->map(function ($brand) {
                            return [
                                'id' => $brand->id,
                                'name' => $brand->name
                            ];
                        })
                    ];
                });

            return response()->json([
                'categories' => $categories
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch categories', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Failed to fetch categories',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

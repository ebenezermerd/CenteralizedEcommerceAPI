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
            Log::info('Product filter request', [
                'params' => $request->all(),
                'page' => $request->input('page', 1),
                'per_page' => $request->input('per_page', 20)
            ]);

            // Start with published products only
            $query = Product::query()
                ->with(['reviews', 'category', 'brand', 'images', 'vendor'])
                ->where('publish', 'published');  // Only get published products

            // Add review stats first for sorting purposes
            $query->withCount('reviews as reviews_count')
                  ->withAvg('reviews as rating_avg', 'rating')
                  ->withSum('reviews as total_ratings', 'rating');

            // Apply sorting first
            $sort = $request->input('sort', 'latest');
            switch ($sort) {
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'popular':
                    $query->orderBy('reviews_count', 'desc')
                          ->orderBy('rating_avg', 'desc');
                    break;
                case 'featured':
                    $query->where('featured', true)
                          ->orderBy('created_at', 'desc');
                    break;
                case 'top_rated':
                    $query->orderBy('rating_avg', 'desc')
                          ->orderBy('reviews_count', 'desc');
                    break;
                case 'price_low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('price', 'desc');
                    break;
                case 'latest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // Then apply filters
            if ($request->has('categoryId')) {
                $query->where('categoryId', $request->categoryId);
            }

            if ($request->has('brandIds')) {
                $brandIds = json_decode($request->brandIds);
                if (!empty($brandIds)) {
                    $query->whereIn('brandId', $brandIds);
                }
            }

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
                    foreach ($request->colors as $colorCode) {
                        $q->orWhereJsonContains('colors', $colorCode);
                    }
                });
            }

            // Apply gender filter
            if ($request->has('gender') && !empty($request->gender)) {
                $query->whereJsonContains('gender', $request->gender);
            }

            // Log the final query for debugging
            Log::debug('Final SQL Query', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            // Paginate results with 20 items per page
            $products = $query->paginate($request->input('per_page', 20));

            Log::info('Products retrieved', [
                'count' => $products->count(),
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage()
            ]);

            return response()->json([
                'products' => ProductResource::collection($products),
                'meta' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Product filtering failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

    public function getColors()
    {
        try {
            $colors = Product::where('publish', 'published')
                ->whereNotNull('colors')
                ->get()
                ->pluck('colors')
                ->flatten()
                ->unique()
                ->values()
                ->map(function ($colorCode) {
                    return [
                        'code' => $colorCode,
                        'name' => $this->getColorName($colorCode)
                    ];
                });

            return response()->json([
                'colors' => $colors
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch colors', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Failed to fetch colors',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getColorName(string $colorCode): string
    {
        $colorMap = [
            '#FF4842' => 'red',
            '#1890FF' => 'blue',
            '#54D62C' => 'green',
            '#FFC107' => 'yellow',
            '#7A0C2E' => 'purple',
            '#000000' => 'black',
            '#FFFFFF' => 'white',
            '#787878' => 'grey',
            '#8B4513' => 'brown',
            '#FF69B4' => 'pink',
            '#FFA500' => 'orange',
            '#FFD700' => 'gold',
            '#C0C0C0' => 'silver',
            '#F5F5DC' => 'beige',
            '#000080' => 'navy',
            '#008080' => 'teal',
        ];

        return $colorMap[strtoupper($colorCode)] ?? 'other';
    }
}

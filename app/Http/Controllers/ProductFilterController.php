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
                $priceRange = json_decode($request->priceRange, true);
                if ($priceRange && is_array($priceRange)) {
                    $query->when($priceRange['start'] > 0, function($q) use ($priceRange) {
                        $q->where('price', '>=', $priceRange['start']);
                    })
                    ->when($priceRange['end'] > 0, function($q) use ($priceRange) {
                        $q->where('price', '<=', $priceRange['end']);
                    });

                    Log::info('Applying price range filter', [
                        'start' => $priceRange['start'],
                        'end' => $priceRange['end']
                    ]);
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

            // Apply tags filter
            if ($request->has('tags') && !empty($request->tags)) {
                $query->where(function($q) use ($request) {
                    foreach ($request->tags as $tag) {
                        $q->orWhereJsonContains('tags', $tag);
                    }
                });
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
            // Get parent categories that either have published products themselves
            // or have children with published products
            $categories = Category::whereNull('parentId')
                ->where(function($query) {
                    $query->whereHas('products', function($q) {
                        $q->where('publish', 'published');
                    })
                    ->orWhereHas('children.products', function($q) {
                        $q->where('publish', 'published');
                    });
                })
                ->with(['children' => function($query) {
                    $query->whereHas('products', function($q) {
                        $q->where('publish', 'published');
                    });
                }])
                ->orWhereHas('children', function($query) {
                    $query->whereHas('products', function($q) {
                        $q->where('publish', 'published');
                    });
                })
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'group' => $category->group,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'coverImg' => $category->coverImg,
                        'isActive' => $category->isActive,
                        'children' => $category->children->map(function ($child) {
                            return [
                                'id' => $child->id,
                                'name' => $child->name,
                                'slug' => $child->slug,
                                'group' => $child->group,
                                'description' => $child->description,
                                'coverImg' => $child->coverImg,
                                'isActive' => $child->isActive,
                            ];
                        })
                    ];
                });

            Log::info('Retrieved categories with children', [
                'parentCount' => $categories->count(),
                'totalChildren' => $categories->sum(function($cat) {
                    return $cat['children']->count();
                })
            ]);

            return response()->json($categories);

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
            '#00AB55' => 'green',
            '#FFC107' => 'yellow',
            '#7F00FF' => 'violet',
            '#000000' => 'black',
            '#FFFFFF' => 'white',
            '#54D62C' => 'green',
            '#2065D1' => 'blue',
            '#919EAB' => 'grey',
            '#FFC0CB' => 'pink',
            '#787878' => 'grey',
            '#8B4513' => 'brown',
            '#FF69B4' => 'pink',
            '#FFA500' => 'orange',
            '#FFD700' => 'gold',
            '#C0C0C0' => 'silver',
            '#F5F5DC' => 'beige',
            '#000080' => 'navy',
            '#008080' => 'teal'
        ];

        return $colorMap[strtoupper($colorCode)] ?? 'other';
    }

    public function getGenders()
    {
        try {
            $genders = Product::where('publish', 'published')
                ->whereNotNull('gender')
                ->get()
                ->pluck('gender')
                ->flatten()
                ->unique()
                ->values()
                ->map(function ($gender) {
                    return [
                        'value' => $gender,
                        'label' => $gender
                    ];
                });

            Log::info('Retrieved unique genders', ['count' => $genders->count()]);

            return response()->json([
                'genders' => $genders
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch genders', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Failed to fetch genders',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getTags()
    {
        try {
            $tags = Product::where('publish', 'published')
                ->whereNotNull('tags')
                ->get()
                ->pluck('tags')
                ->flatten()
                ->unique()
                ->values()
                ->map(function ($tag) {
                    return [
                        'value' => $tag,
                        'label' => $tag
                    ];
                });

            Log::info('Retrieved unique tags', ['count' => $tags->count()]);

            return response()->json([
                'tags' => $tags
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch tags', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Failed to fetch tags',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getFeaturedCategories()
    {
        try {
            $categories = Category::with('parent')
                ->whereHas('products', function($query) {
                    $query->where('publish', 'published');
                })
                ->select('id', 'name', 'coverImg', 'description', 'parentId')
                ->withCount(['products' => function($query) {
                    $query->where('publish', 'published');
                }])
                ->orderBy('products_count', 'desc')
                ->limit(12)
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->parentId ? $category->parent->id : $category->id,
                        'label' => $category->name,
                        'icon' => $category->coverImg ?: null,
                        'description' => $category->description,
                        'productsCount' => $category->products_count,
                        // Include subcategory info if this is a child category
                        'subCategoryId' => $category->parentId ? $category->id : null,
                        'parentName' => $category->parentId ? $category->parent->name : null
                    ];
                });

            Log::info('Featured categories retrieved', [
                'count' => $categories->count(),
                'categories' => $categories->pluck('label')
            ]);

            return response()->json([
                'categories' => $categories
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch featured categories', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Failed to fetch featured categories',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

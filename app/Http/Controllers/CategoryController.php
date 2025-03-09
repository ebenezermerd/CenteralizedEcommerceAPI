<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\CategoryService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\CategoryNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {
        Log::info('CategoryController initialized');
    }

    public function index(): JsonResponse
    {
        Log::debug('Fetching all categories');
        $categories = $this->categoryService->getAllCategories();
        Log::info('Retrieved categories', ['count' => $categories->count()]);
        return response()->json(
            CategoryResource::collection($categories),
            Response::HTTP_OK
        );
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        Log::debug('Creating new category', ['data' => $request->validated()]);
        $category = $this->categoryService->createCategory($request->validated());
        Log::info('Category created', ['id' => $category->id]);
        return response()->json(
            new CategoryResource($category),
            Response::HTTP_CREATED
        );
    }

    public function all(): JsonResponse
    {
        Log::debug('Fetching all categories with basic fields');
        $categories = Category::all(['id', 'name', 'parentId', 'group']);
        Log::info('Retrieved all categories', ['count' => $categories->count()]);
        return response()->json($categories);
    }

    public function show(Category $category): JsonResponse
    {
        Log::debug('Showing category details', ['id' => $category->id]);
        return response()->json(
            new CategoryResource($category->load(['parent', 'children'])),
            Response::HTTP_OK
        );
    }

    public function subcategories(string $group): JsonResponse
    {
        Log::debug('Fetching subcategories for group', ['group' => $group]);
        $subcategories = Category::where('group', $group)
            ->whereNotNull('parentId')
            ->get(['id', 'name']);
        Log::info('Retrieved subcategories', ['count' => $subcategories->count()]);
        return response()->json($subcategories);
    }

    public function findByName(string $name): JsonResponse
    {
        Log::debug('Finding category by name', ['name' => $name]);
        $category = Category::where('name', $name)
            ->first(['id', 'name', 'parentId', 'group']);

        if (!$category) {
            Log::warning('Category not found', ['name' => $name]);
            return response()->json(['message' => 'Category not found'], 404);
        }

        Log::info('Category found', ['id' => $category->id]);
        return response()->json($category);
    }

    public function findProductCategory(string $name): JsonResponse
    {
        Log::debug('Finding product category', ['name' => $name]);
        $category = Category::where('name', $name)->first();

        if (!$category) {
            Log::warning('Invalid product category', ['name' => $name]);
            return response()->json([
                'message' => 'Invalid product category. Please select a valid subcategory.',
                'validCategories' => Category::pluck('name')
            ], 404);
        }

        Log::info('Product category found', ['id' => $category->id]);
        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'group' => $category->group,
            'isValid' => true,
        ]);
    }

    public function debug(): JsonResponse
    {
        Log::debug('Running category debug endpoint');
        $categories = Category::select('id', 'name', 'group')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'group' => $category->group,
                    'nameLower' => strtolower($category->name)
                ];
            });

        Log::info('Debug data retrieved', ['total' => $categories->count()]);
        return response()->json([
            'categories' => $categories,
            'total' => $categories->count(),
            'note' => 'This is a debug endpoint to check available categories'
        ]);
    }

    public function getBySlug(string $slug): JsonResponse
    {
        Log::debug('Finding category by slug', ['slug' => $slug]);
        try {
            $category = $this->categoryService->findCategoryBySlug($slug);
            Log::info('Category found by slug', ['id' => $category->id]);
            return response()->json(
                new CategoryResource($category),
                Response::HTTP_OK
            );
        } catch (CategoryNotFoundException $e) {
            Log::error('Category not found by slug', ['slug' => $slug, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Category not found',
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getStructure(): JsonResponse
    {
        Log::debug('Fetching category structure');
        $structure = $this->categoryService->getCategoryStructure();
        Log::info('Category structure retrieved');
        return response()->json($structure, Response::HTTP_OK);
    }

    public function getBrands(string $categoryId): JsonResponse
    {
        Log::debug('Fetching brands for category', ['categoryId' => $categoryId]);
        try {
            $category = Category::findOrFail($categoryId);

            $brands = $category->brands()
                ->whereHas('products', function($query) {
                    $query->where('publish', 'published');
                })
                ->select('brands.id', 'brands.name', 'brands.description', 'brands.logo')
                ->get()
                ->map(function ($brand) {
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'description' => $brand->description,
                        'logo' => $brand->logo
                    ];
                });

            Log::info('Retrieved brands with products for category', [
                'categoryId' => $categoryId,
                'brandsCount' => $brands->count()
            ]);

            return response()->json($brands);

        } catch (ModelNotFoundException $e) {
            Log::error('Category not found', ['categoryId' => $categoryId]);
            return response()->json([
                'error' => 'Category not found',
                'message' => 'The specified category does not exist'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Failed to fetch brands', [
                'categoryId' => $categoryId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'Failed to fetch brands',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function validateCategoryName(string $name): JsonResponse
    {
        Log::debug('Validating category name', ['name' => $name]);
        $exists = $this->categoryService->validateCategory($name);
        Log::info('Category validation result', ['name' => $name, 'exists' => $exists]);
        return response()->json(['valid' => $exists], Response::HTTP_OK);
    }
}

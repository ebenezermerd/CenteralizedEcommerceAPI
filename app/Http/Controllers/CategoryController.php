<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CategoryService;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\CategoryNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    public function index(): JsonResponse
    {
        $categories = $this->categoryService->getAllCategories();
        return response()->json(
            CategoryResource::collection($categories),
            Response::HTTP_OK
        );
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());
        return response()->json(
            new CategoryResource($category),
            Response::HTTP_CREATED
        );
    }

    public function all(): JsonResponse
    {
        $categories = Category::all(['id', 'name', 'parentId', 'group']);
        return response()->json($categories);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json(
            new CategoryResource($category->load(['parent', 'children'])),
            Response::HTTP_OK
        );
    }

    public function subcategories(string $group): JsonResponse
    {
        $subcategories = Category::where('group', $group)
            ->whereNotNull('parentId')
            ->get(['id', 'name']);

        return response()->json($subcategories);
    }

    public function findByName(string $name): JsonResponse
    {
        $category = Category::where('name', $name)
            ->first(['id', 'name', 'parentId', 'group']);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category);
    }

    public function findProductCategory(string $name): JsonResponse
    {
        $category = Category::where('name', $name)->first();

        if (!$category) {   
            return response()->json([
                'message' => 'Invalid product category. Please select a valid subcategory.',
                'validCategories' => Category::pluck('name')
            ], 404);
        }

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'group' => $category->group,
            'isValid' => true,
        ]);
    }

    public function debug(): JsonResponse
    {
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

        return response()->json([
            'categories' => $categories,
            'total' => $categories->count(),
            'note' => 'This is a debug endpoint to check available categories'
        ]);
    }

    public function getBySlug(string $slug): JsonResponse
    {
        try {
            $category = $this->categoryService->findCategoryBySlug($slug);
            return response()->json(
                new CategoryResource($category),
                Response::HTTP_OK
            );
        } catch (CategoryNotFoundException $e) {
            return response()->json([
                'error' => 'Category not found',
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getStructure(): JsonResponse
    {
        $structure = $this->categoryService->getCategoryStructure();
        return response()->json($structure, Response::HTTP_OK);
    }

    public function getBrands(string $categoryName): JsonResponse
    {
        try {
            $category = Category::where('name', $categoryName)->firstOrFail();
            $brands = $this->categoryService->getCategoryBrands($category->name);
            
            return response()->json($brands, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Category not found',
                'message' => "Category '{$categoryName}' does not exist"
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function validate(string $name): JsonResponse
    {
        $exists = $this->categoryService->validateCategory($name);
        return response()->json(['valid' => $exists], Response::HTTP_OK);
    }
}

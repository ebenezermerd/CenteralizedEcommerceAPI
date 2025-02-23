<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CategoryService;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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

    public function show(string $name): JsonResponse
    {
        $category = Category::where('name', $name)
            ->with(['parent', 'children'])
            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category);
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
}

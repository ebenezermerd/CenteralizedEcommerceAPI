<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Category;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['reviews', 'category', 'images'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(12);

        return response()->json([
            'products' => ProductResource::collection($products)
        ], 201);
    }

    public function store(ProductRequest $request)
    {
        try {
            DB::beginTransaction();

            $categoryName = trim(str_replace('"', '', $request->category));
            $category = Category::findByNameStrict($categoryName);
            if (!$category) {
                throw new \Exception('Category not found');
            }

            // Create or update the product
            $product = $request->id
                ? Product::findOrFail($request->id)
                : new Product();

            $product->fill(array_merge(
                $request->except(['coverUrl', 'images', 'category', 'id']),
                ['categoryId' => $category->id, 'available' => $request->quantity]
            ));
            $product->save();

            // Handle both coverUrl and images
            $processImage = function ($image, $isPrimary = false) use ($product) {
                if (is_file($image)) {
                    $path = $image->store('products', 'public');
                    ProductImage::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'is_primary' => $isPrimary
                        ],
                        ['image_path' => $path]
                    );
                    return $path;
                }
                return is_string($image) ? $image : null;
            };

            // Process cover image
            if ($request->has('coverUrl')) {
                $coverPath = $processImage($request->coverUrl, true);
                if ($coverPath) {
                    $product->update(['coverUrl' => $coverPath]);
                }
            }

            // Process additional images
            if ($request->has('images')) {
                foreach ($request->images as $image) {
                    $processImage($image, false);
                }
            }

            DB::commit();
            return new ProductResource($product);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error processing product', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(ProductRequest $request, $id)
    {
        $request->merge(['id' => $id]);
        return $this->store($request);
    }

    public function show(Request $request)
    {
        try {
            $product = Product::with(['reviews', 'category', 'images'])
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->findOrFail($request->productId);

            return response()->json([
                'product' => new ProductResource($product)
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }
}

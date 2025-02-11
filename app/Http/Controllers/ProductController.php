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
            if (env('APP_ENV') === 'local') {
                \Sentry\captureMessage('Product index accessed');
            }
        return response()->json([
            'products' => ProductResource::collection($products)
        ], 201);
    }

    public function store(ProductRequest $request)
    {

        try {

            DB::beginTransaction();
            \Log::info('Raw Request from controller Data:', $request->all());
            \Log::info('Raw Request from controller input request:', ['coverUrl' => $request->cover_img]);

            $categoryName = trim(str_replace('"', '', $request->category));
            $category = Category::findByNameStrict($categoryName);
            if (!$category) {
                \Log::error('Category not found', ['category_name' => $categoryName]);
                throw new \Exception('Category not found');
            }

            // Create or update the product
            $product = $request->id
                ? Product::findOrFail($request->id)
                : new Product();

            \Log::info('Processing product data', [
                'product_id' => $product->id ?? 'new',
                'category_id' => $category->id
            ]);

                // Remove the $processImage closure and file upload logic.
                // Trust the ProductRequest to have already stored files and provided paths.

                // Update cover image:
                if ($request->filled('coverUrl')) {
                    $product->update(['coverUrl' => $request->cover_url]);
                }

                // Update additional images:
                $images = $request->input('images', []);
                if (!empty($images)) {
                    $product->images()->where('is_primary', false)->delete();
                    \Log::info('Accessing additional images', ['images' => $images]);
                    foreach ($images as $imagePath) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $imagePath,
                            'is_primary' => false,
                        ]);
                    }
                }
                if ($request->cover_img) {
                    $product->images()->where('is_primary', true)->delete();
                    $product->update(['coverUrl' => $request->cover_img]);
                    \Log::info('Cover URL updated', [
                        'cover_url' => $request->cover_img,
                        'product_url' => $product->coverUrl,
                    ]);
                }
                $product->load(['images']); // Refresh the relationship to get updated images
                $product->refresh(); // Refresh the model to get updated coverUrl

            DB::commit();
            \Log::info('Product stored successfully', [
                'product_id' => $product->id,
                'product' => $product->toArray(),
            ]);

            return new ProductResource($product);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
            \Log::info('Fetching product details', [
                'product_id' => $request->productId
            ]);

            $product = Product::with(['reviews', 'category', 'images'])
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->findOrFail($request->productId);

            \Log::info('Product found successfully', [
                'product_id' => $request->productId,
                'product' => $product->toArray()
            ]);

            return response()->json([
                'product' => new ProductResource($product)
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error fetching product', [
                'product_id' => $request->productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Product not found'], 404);
        }
    }
    //delete product
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            // Delete related images
            $product->images()->delete();

            $product->delete();
            return response()->json(['message' => 'Product and related images deleted'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }
}

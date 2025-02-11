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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class ProductController extends Controller
{
    /**
 * @group Products
 *
 * Retrieve a list of products with their details.
 *
 * This endpoint returns a paginated list of products including their reviews,
 * categories, images, and vendor information. The user needs to be authenticated
 * to view the products.
 *
 * @queryParam page int optional The page number (default: 1).
 * @queryParam per_page int optional The number of items per page (default: 12).
 *
 * @response 201 {
 *  "products": [
 *      {
 *          "id": "string",
 *          "name": "string",
 *          "sku": "string",
 *          "code": "string",
 *          "description": "string",
 *          "subDescription": "string",
 *          "publish": "string",
 *          "vendor": {
 *              "id": "string",
 *              "name": "string",
 *              "email": "string",
 *              "phone": "string"
 *          },
 *          "coverUrl": "string|null",
 *          "images": ["string"],
 *          "price": "float",
 *          "priceSale": "float",
 *          "taxes": "float",
 *          "tags": ["string"],
 *          "sizes": ["string"],
 *          "colors": ["string"],
 *          "gender": ["string"],
 *          "inventoryType": "string",
 *          "quantity": "int",
 *          "available": "boolean",
 *          "totalSold": "int",
 *          "category": "string|null",
 *          "totalRatings": "float",
 *          "totalReviews": "int",
 *          "reviews": [
 *              {
 *                  "id": "string",
 *                  "name": "string",
 *                  "postedAt": "string",
 *                  "comment": "string",
 *                  "isPurchased": "boolean",
 *                  "rating": "float",
 *                  "avatarUrl": "string|null",
 *                  "helpful": "int",
 *                  "attachments": ["string"]
 *              }
 *          ],
 *          "ratings": [
 *              {
 *                  "name": "string",
 *                  "starCount": "int",
 *                  "reviewCount": "int"
 *              }
 *          ],
 *          "newLabel": {
 *              "enabled": "boolean",
 *              "content": "string"
 *          },
 *          "saleLabel": {
 *              "enabled": "boolean",
 *              "content": "string"
 *          },
 *          "createdAt": "string"
 *      }
 *  ]
 * }
 *
 * @response 401 {
 *  "message": "Unauthenticated."
 * }
 */
    public function index(Request $request)
    {
        $user = auth()->user();
        $products = Product::with(['reviews', 'category', 'images', 'vendor' => function ($query) {
            $query->select('id', 'firstName', 'lastName', 'phone', 'email')
                ->selectRaw("CONCAT(firstName, ' ', lastName) as name");
        }])
            ->viewableBy($user)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(12);

        // Log the result for debugging
        \Log::info('Products Data', $products->toArray());

        return response()->json([
            'products' => ProductResource::collection($products)
        ], 201);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Starting product creation', ['request' => $request->except(['coverUrl', 'images'])]);

            // Pre-process arrays that might come as JSON strings
            $arrayFields = ['colors', 'sizes', 'gender', 'tags'];
            foreach ($arrayFields as $field) {
                if (is_string($request->$field)) {
                    $request->merge([
                        $field => json_decode($request->$field, true)
                    ]);
                }
            }

            // 1. Validate the request
            $validated = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'sku' => 'nullable|string|unique:products,sku',
                'code' => 'nullable|string|unique:products,code',
                'description' => 'required|string',
                'subDescription' => 'required|string',
                'quantity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:1',
                'priceSale' => 'nullable|numeric|min:0',
                'taxes' => 'nullable|numeric|min:0',
                'colors' => 'required|array|min:1',
                'sizes' => 'required|array|min:1',
                'gender' => 'required|array|min:1',
                'tags' => 'required|array|min:1',
                'category' => 'required|string',
                'publish' => 'required|in:draft,published',
                'coverUrl' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048',
                'images' => 'required|array',
                'images.*' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048',
                'saleLabel' => 'required|json',
                'newLabel' => 'required|json'
            ]);

            if ($validated->fails()) {
                return response()->json(['errors' => $validated->errors()], 422);
            }

            // 2. Get or validate category
            $category = Category::where('name', trim($request->category))->first();
            if (!$category) {
                return response()->json(['message' => 'Invalid category'], 422);
            }

            // 3. Create product
            $product = new Product();
            $product->fill([
                'name' => $request->name,
                'sku' => $request->sku,
                'code' => $request->code,
                'description' => $request->description,
                'subDescription' => $request->subDescription,
                'quantity' => $request->quantity,
                'available' => $request->quantity,
                'price' => $request->price,
                'priceSale' => $request->priceSale,
                'taxes' => $request->taxes,
                'colors' => $request->colors,
                'sizes' => $request->sizes,
                'gender' => $request->gender,
                'tags' => $request->tags,
                'categoryId' => $category->id,
                'publish' => $request->publish,
                'saleLabel' => json_decode($request->saleLabel, true),
                'newLabel' => json_decode($request->newLabel, true),
                'vendor_id' => auth()->id()
            ]);

            // 4. Handle images
            $processedImages = $this->processImages($request);
            if (isset($processedImages['error'])) {
                return response()->json(['error' => $processedImages['error']], 500);
            }

            $product->coverUrl = $processedImages['coverUrl'];
            $product->save();

            // 5. Store additional images
            if (!empty($processedImages['additionalImages'])) {
                foreach ($processedImages['additionalImages'] as $imagePath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'is_primary' => false
                    ]);
                }
            }

            DB::commit();
            Log::info('Product created successfully', ['product_id' => $product->id]);

            return response()->json([
                'message' => 'Product created successfully',
                'product' => new ProductResource($product)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function validateRequest(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if (!empty($value)) { // Only check uniqueness if value is provided
                        $exists = Product::where('sku', $value)
                            ->when($request->id, function ($query) use ($request) {
                                return $query->where('id', '!=', $request->id);
                            })
                            ->exists();

                        if ($exists) {
                            $fail('This SKU already exists!');
                        }
                    }
                }
            ],
            'code' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if (!empty($value)) { // Only check uniqueness if value is provided
                        $exists = Product::where('code', $value)
                            ->when($request->id, function ($query) use ($request) {
                                return $query->where('id', '!=', $request->id);
                            })
                            ->exists();

                        if ($exists) {
                            $fail('This code already exists!');
                        }
                    }
                }
            ],
            'description' => 'required|string',
            'subDescription' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:1',
            'priceSale' => 'nullable|numeric|min:0',
            'taxes' => 'nullable|numeric|min:0',
            'colors' => 'required|array|min:1',
            'sizes' => 'required|array|min:1',
            'gender' => 'required|array|min:1',
            'tags' => 'required|array|min:1',
            'category' => 'required|string',
            'publish' => 'required|in:draft,published',
            'coverUrl' => 'required',
            'images' => 'required|array',
            'images.*' => 'required',
            'saleLabel' => 'required|json',
            'newLabel' => 'required|json'
        ]);

        if ($validator->fails()) {
            return ['error' => ['message' => 'Validation failed', 'errors' => $validator->errors()]];
        }

        // Validate category
        $category = Category::findByNameStrict(trim($request->category, '"'));
        if (!$category) {
            return ['error' => ['message' => 'Invalid category']];
        }

        // Process and validate images
        $processedImages = $this->processImages($request);
        if (isset($processedImages['error'])) {
            return ['error' => ['message' => $processedImages['error']]];
        }

        return [
            'data' => array_merge($validator->validated(), [
                'categoryId' => $category->id,
                'coverUrl' => $processedImages['coverUrl'],
                'images' => $processedImages['additionalImages']
            ])
        ];
    }

    protected function processImages(Request $request): array
    {
        try {
            // Process cover image
            $coverUrl = null;
            if ($request->hasFile('coverUrl')) {
                $cover = $request->file('coverUrl');
                if ($cover->isValid() && in_array($cover->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $coverUrl = $cover->store('products/covers', 'public');
                }
            }

            // Process additional images
            $additionalImages = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    if ($image->isValid() && in_array($image->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif'])) {
                        $additionalImages[] = $image->store('products/images', 'public');
                    }
                }
            }

            return [
                'coverUrl' => $coverUrl,
                'additionalImages' => $additionalImages
            ];
        } catch (\Exception $e) {
            Log::error('Image processing failed', ['error' => $e->getMessage()]);
            throw new \Exception('Error processing images: ' . $e->getMessage());
        }
    }

    protected function saveProduct(Product $product, array $data): void
    {
        try {
            // Convert empty strings to null for sku and code
            $data['sku'] = !empty($data['sku']) ? $data['sku'] : null;
            $data['code'] = !empty($data['code']) ? $data['code'] : null;

            // Process images first
            $processedImages = $this->processImages(request());

            // Fill product data
            $product->fill(array_merge(
                collect($data)->except(['images', 'category'])->toArray(),
                ['available' => $data['quantity']]
            ));

            $product->save();

            // Save additional images
            if (!empty($processedImages['additionalImages'])) {
                $product->images()->delete(); // Remove old images
                foreach ($processedImages['additionalImages'] as $imagePath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'is_primary' => false
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error saving product with images', ['error' => $e->getMessage()]);
            throw new \Exception('Error saving product: ' . $e->getMessage());
        }
    }

    protected function canManageProduct(Product $product): bool
    {
        $user = auth()->user();
        return $user->hasRole('admin') || $product->vendor_id === $user->id;
    }

    protected function getVendorId(?string $vendorId): string
    {
        $user = auth()->user();
        if ($user->hasRole('admin') && $vendorId) {
            return User::role('supplier')->findOrFail($vendorId)->id;
        }
        return $user->id;
    }

    public function update(Request $request, string $id)
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($id);

            if (!$this->canManageProduct($product)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Clean up old images if new ones are provided
            if ($request->hasFile('coverUrl') || $request->hasFile('images')) {
                $this->cleanupOldImages($product);
            }

            $this->saveProduct($product, $request->all());

            DB::commit();

            return new ProductResource($product->fresh());

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error updating product', 'error' => $e->getMessage()], 500);
        }
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

    public function transferVendor(Request $request, $id)
    {
        try {
            $request->validate([
                'new_vendor_id' => 'required|exists:users,id'
            ]);

            $user = auth()->user();
            if (!$user->hasRole('admin')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $product = Product::findOrFail($id);
            $newVendor = User::role('supplier')->findOrFail($request->new_vendor_id);

            $product->vendor_id = $newVendor->id;
            $product->save();

            // Log the transfer
            activity()
                ->performedOn($product)
                ->causedBy($user)
                ->withProperties([
                    'old_vendor_id' => $product->vendor_id,
                    'new_vendor_id' => $newVendor->id
                ])
                ->log('product_vendor_transferred');

            return response()->json([
                'message' => 'Product vendor transferred successfully',
                'product' => new ProductResource($product)
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error transferring product vendor', 'error' => $e->getMessage()], 500);
        }
    }

    protected function cleanupOldImages(Product $product): void
    {
        try {
            // Delete old cover image if exists
            if ($product->coverUrl && !str_starts_with($product->coverUrl, 'http')) {
                Storage::disk('public')->delete($product->coverUrl);
            }

            // Delete old additional images
            foreach ($product->images as $image) {
                if (!str_starts_with($image->image_path, 'http')) {
                    Storage::disk('public')->delete($image->image_path);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup old images', ['error' => $e->getMessage()]);
        }
    }
}

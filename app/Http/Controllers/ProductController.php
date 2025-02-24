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
use App\Mail\CompanyApprovalRequired;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Services\CategoryService;
use App\Models\Brand;

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

        // Customers can see only published products
        if ($user && $user->hasRole('customer')) {
            $products = Product::with(['reviews', 'category', 'brand', 'images', 'vendor' => function ($query) {
                $query->select('id', 'firstName', 'lastName', 'phone', 'email')
                    ->selectRaw("CONCAT(firstName, ' ', lastName) as name");
            }])
                ->published()
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->latest()
                ->paginate(12);
        } else if ($request->url() == 'https://www.korecha.com.et/') {
            // Guests accessing from specific URL can see only published products
            $products = Product::with(['reviews', 'category', 'brand', 'images', 'vendor' => function ($query) {
                $query->select('id', 'firstName', 'lastName', 'phone', 'email')
                    ->selectRaw("CONCAT(firstName, ' ', lastName) as name");
            }])
                ->published()
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->latest()
                ->paginate(12);
        } else if ($user) {
            // Admin and supplier can see all products
            $products = Product::with(['reviews', 'category', 'brand', 'images', 'vendor' => function ($query) {
                $query->select('id', 'firstName', 'lastName', 'phone', 'email')
                    ->selectRaw("CONCAT(firstName, ' ', lastName) as name");
            }])
                ->viewableBy($user)
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->latest()
                ->paginate(12);
        } else {
            // Default guest access (from non-specific URLs)
            $products = Product::with(['reviews', 'category', 'brand', 'images', 'vendor' => function ($query) {
                $query->select('id', 'firstName', 'lastName', 'phone', 'email')
                    ->selectRaw("CONCAT(firstName, ' ', lastName) as name");
            }])
                ->published()
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->latest()
                ->paginate(12);
        }

        // Log the result for debugging
        \Log::info('Products Data', $products->toArray());

        return response()->json([
            'products' => ProductResource::collection($products)
        ], 200); // Changed from 201 to 200 as this is a GET request
    }

    private function checkSupplierEligibility()
    {
        $user = auth()->user();

        if (!$user->hasRole('supplier') && !$user->hasRole('admin')) {
            return [
                'eligible' => false,
                'message' => 'Only suppliers and admins can create products',
                'status' => 403
            ];
        }

        if ($user->hasRole('supplier')) {
            $company = $user->company;

            if (!$company) {
                return [
                    'eligible' => false,
                    'message' => 'No company found for this supplier',
                    'status' => 404
                ];
            }

            if ($company->status !== 'active') {
                // Send email notification
                Mail::to($user->email)
                    ->send(new CompanyApprovalRequired($company));

                return [
                    'eligible' => false,
                    'message' => 'Your company account requires approval before you can create products. Please check your email for more information.',
                    'status' => 403
                ];
            }
        }

        return ['eligible' => true];
    }

    public function store(Request $request)
    {
        // Check supplier eligibility
        $eligibilityCheck = $this->checkSupplierEligibility();
        if (!$eligibilityCheck['eligible']) {
            return response()->json([
                'message' => $eligibilityCheck['message']
            ], $eligibilityCheck['status']);
        }

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

            // Log brand data before validation
            Log::info('Brand data before validation', [
                'brand_data' => $request->brand,
            ]);

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
                'coverUrl' => ['nullable', function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        if (!is_string($value) && !$value instanceof \Illuminate\Http\UploadedFile) {
                            $fail('The cover url must be either a valid URL or an image file.');
                        }
                        if ($value instanceof \Illuminate\Http\UploadedFile) {
                            $allowedTypes = ['jpeg', 'png', 'jpg', 'gif'];
                            if (!in_array($value->getClientOriginalExtension(), $allowedTypes)) {
                                $fail('The cover url must be a file of type: jpeg, png, jpg, gif.');
                            }
                        }
                    }
                }],
                'images' => 'nullable|array',
                'images.*' => [function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        if (!is_string($value) && !$value instanceof \Illuminate\Http\UploadedFile) {
                            $fail('Each image must be either a valid URL or an image file.');
                        }
                        if ($value instanceof \Illuminate\Http\UploadedFile) {
                            $allowedTypes = ['jpeg', 'png', 'jpg', 'gif'];
                            if (!in_array($value->getClientOriginalExtension(), $allowedTypes)) {
                                $fail('Each image must be a file of type: jpeg, png, jpg, gif.');
                            }
                        }
                    }
                }],
                'saleLabel' => 'required|json',
                'newLabel' => 'required|json',
                'brand' => 'nullable|string|exists:brands,id'
            ]);

            if ($validated->fails()) {
                Log::error('Validation failed for brand data', [
                    'brand_errors' => $validated->errors()->get('brand')
                ]);
                return response()->json(['errors' => $validated->errors()], 422);
            }

            $this->validateCategoryAndBrand($request->all());

            $category = Category::where('name', $request->category)->first();
            $brandId = $this->handleBrand($request->brand, $request->category);

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
                'vendor_id' => auth()->id(),
                'brand' => $brandId,
            ]);

            // 4. Handle images
            $processedImages = $this->processImages($request);
            if (isset($processedImages['error'])) {
                return response()->json(['error' => $processedImages['error']], 500);
            }

            $product->coverUrl = $processedImages['coverUrl'];
            $product->save();

            // Store additional images
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

            // Log the product creation
            Log::info('Product created successfully', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'vendor_id' => $product->vendor_id,
                'brand' => $product->brand
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'product' => new ProductResource($product)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating product with brand data', [
                'brand_data' => $request->brand ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        }
    }

    // Validate the request
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
            'newLabel' => 'required|json',
            'brand' => 'nullable|string|exists:brands,id'
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
            $coverUrl = null;
            if ($request->has('coverUrl')) {
                if ($request->hasFile('coverUrl')) {
                    $cover = $request->file('coverUrl');
                    if ($cover->isValid() && in_array($cover->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif'])) {
                        $coverUrl = $cover->store('products/covers', 'public');
                    }
                } else if (is_string($request->coverUrl) && filter_var($request->coverUrl, FILTER_VALIDATE_URL)) {
                    $coverUrl = $request->coverUrl;
                }
            }

            $additionalImages = [];
            if ($request->has('images')) {
                foreach ($request->images as $image) {
                    if ($image instanceof \Illuminate\Http\UploadedFile) {
                        if ($image->isValid() && in_array($image->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif'])) {
                            $additionalImages[] = $image->store('products/images', 'public');
                        }
                    } else if (is_string($image) && filter_var($image, FILTER_VALIDATE_URL)) {
                        $additionalImages[] = $image;
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

            Log::info('Starting product update - Brand Data Debug', [
                'product_id' => $id,
                'raw_brand_data' => $request->brand,
                'brand_content_type' => gettype($request->brand)
            ]);

            $product = Product::findOrFail($id);

            if (!$this->canManageProduct($product)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Clean up old images only if new files are provided
            $arrayFields = ['colors', 'sizes', 'gender', 'tags'];
            foreach ($arrayFields as $field) {
                if (is_string($request->$field)) {
                    $request->merge([
                        $field => json_decode($request->$field, true)
                    ]);
                }
            }

            // Log brand data before validation
            Log::info('Brand data before validation', [
                'product_id' => $id,
                'brand_data' => $request->brand,

            ]);

            // 1. Validate the request
            $validated = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'sku' => 'nullable|string|unique:products,sku,' . $id,
                'code' => 'nullable|string|unique:products,code,' . $id,
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
                'coverUrl' => ['nullable', function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        if (!is_string($value) && !$value instanceof \Illuminate\Http\UploadedFile) {
                            $fail('The cover url must be either a valid URL or an image file.');
                        }
                        if ($value instanceof \Illuminate\Http\UploadedFile) {
                            $allowedTypes = ['jpeg', 'png', 'jpg', 'gif'];
                            if (!in_array($value->getClientOriginalExtension(), $allowedTypes)) {
                                $fail('The cover url must be a file of type: jpeg, png, jpg, gif.');
                            }
                        }
                    }
                }],
                'images' => 'nullable|array',
                'images.*' => [function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        if (!is_string($value) && !$value instanceof \Illuminate\Http\UploadedFile) {
                            $fail('Each image must be either a valid URL or an image file.');
                        }
                        if ($value instanceof \Illuminate\Http\UploadedFile) {
                            $allowedTypes = ['jpeg', 'png', 'jpg', 'gif'];
                            if (!in_array($value->getClientOriginalExtension(), $allowedTypes)) {
                                $fail('Each image must be a file of type: jpeg, png, jpg, gif.');
                            }
                        }
                    }
                }],
                'saleLabel' => 'required|json',
                'newLabel' => 'required|json',
                'brand' => 'nullable|string|exists:brands,id'
            ]);

            if ($validated->fails()) {
                Log::error('Validation failed for brand data', [
                    'product_id' => $id,
                    'brand_errors' => $validated->errors()->get('brand')
                ]);
                return response()->json(['errors' => $validated->errors()], 422);
            }

            // Log brand data after validation
            Log::info('Brand data after validation', [
                'product_id' => $id,
                'validated_brand' => $request->brand,

            ]);

            // 2. Get or validate category
            $category = Category::where('name', trim($request->category))->first();
            if (!$category) {
                return response()->json(['message' => 'Invalid category'], 422);
            }

            $brandData = $this->handleBrand($request->brand, $category);


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
                'brandId' => $brandData,
                'vendor_id' => auth()->id()
            ]);

            // Log brand data after fill
            Log::info('Brand data after product fill', [
                'product_id' => $id,
                'product_brand' => $product->brand
            ]);

            // 4. Handle images
            $processedImages = $this->processImages($request);
            if (isset($processedImages['error'])) {
                return response()->json(['error' => $processedImages['error']], 500);
            }

            if ($processedImages['coverUrl']) {
                $product->coverUrl = $processedImages['coverUrl'];
            }

            $product->save();

            // Log final brand data after save
            Log::info('Final brand data after save', [
                'product_id' => $id,
                'final_brand' => $product->fresh()->brand
            ]);

            // 5. Store additional images
            if (!empty($processedImages['additionalImages'])) {
                // Delete existing images
                ProductImage::where('product_id', $product->id)->delete();
                foreach ($processedImages['additionalImages'] as $imagePath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'is_primary' => false
                    ]);
                }
            }

            DB::commit();
            Log::info('Product updated successfully with brand data', [
                'product_id' => $product->id,
                'final_brand_state' => $product->brand
            ]);

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => new ProductResource($product)
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating product with brand data', [
                'product_id' => $id,
                'brand_data' => $request->brand ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error updating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            \Log::info('Fetching product details', [
                'product_id' => $request->productId
            ]);

            $product = Product::with(['reviews', 'category', 'brand', 'images'])
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

    public function publishChange(Request $request, string $id)
    {
        try {
            $product = Product::findOrFail($id);

            if (!$this->canManageProduct($product)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = Validator::make($request->all(), [
                'publish' => 'required|string|in:draft,published'
            ]);

            if ($validated->fails()) {
                return response()->json(['errors' => $validated->errors()], 422);
            }

            $product->publish = $request->publish;
            $product->save();

            Log::info('Product publish status updated', [
                'product_id' => $id,
                'publish_status' => $request->publish,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Product visibility updated successfully',
                'product' => new ProductResource($product)
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating product publish status', [
                'product_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Error updating product visibility',
                'error' => $e->getMessage()
            ], 500);
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

    /**
     * @group Products
     *
     * Search published products by name
     *
     * @queryParam q string required Search query
     *
     * @response 200 {
     *   "products": [
     *     {
     *       "id": "string",
     *       "name": "string",
     *       "coverImg": "string",
     *       "price": "float",
     *       "priceSale": "float",
     *       "caption": "string"
     *     }
     *   ]
     * }
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');

        if (empty($query)) {
            return response()->json(['products' => []]);
        }

        $products = Product::where('publish', 'published')
            ->where('name', 'LIKE', "%{$query}%")
            ->with(['category', 'brand', 'images'])
            ->limit(10)
            ->get();

        return response()->json([
            'products' => ProductResource::collection($products)
        ]);
    }

    protected function validateCategoryAndBrand(array $data): void
    {
        if (!isset($data['category'])) {
            throw new ValidationException('Category is required');
        }

        $category = Category::where('name', $data['category'])
            ->with('brands')
            ->first();

        if (!$category) {
            throw new ValidationException("Invalid category: {$data['category']}");
        }

        // Validate brand ID if provided
        if (isset($data['brand']) && $data['brand'] !== null) {
            $brandExists = $category->brands()
                ->where('brands.id', $data['brand'])
                ->exists();

            if (!$brandExists) {
                throw new ValidationException("Invalid brand ID for category {$data['category']}");
            }
        }
    }

    protected function handleBrand($brandId, $category)
    {
        if (!$brandId) return null;

        $brand = Brand::where('id', $brandId)
            ->whereHas('categories', function($query) use ($category) {
                $query->where('name', $category);
            })
            ->first();

        if (!$brand) {
            throw new ValidationException("Brand not found or not associated with category");
        }

        return $brand->id; // Returns string ID
    }

    protected function validateBrandData($brand, $category)
    {
        if (!$brand) {
            return true; // Brand is optional
        }

        $validator = Validator::make(['brand' => $brand], [
            'brand' => 'json'
        ]);

        if ($validator->fails()) {
            throw new ValidationException("Invalid brand data format");
        }

        $brandData = json_decode($brand, true);
        if (!isset($brandData['name'])) {
            throw new ValidationException("Brand name is required");
        }

        // Verify brand exists for category
        $availableBrands = app(CategoryService::class)->getCategoryBrands($category);
        $brandExists = collect($availableBrands)->contains('name', $brandData['name']);

        if (!$brandExists) {
            throw new ValidationException("Brand '{$brandData['name']}' is not available for category '{$category}'");
        }

        return true;
    }
}

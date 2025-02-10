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
    $products = Product::with(['reviews', 'category', 'images', 'vendor' => function($query) {
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

    public function store(ProductRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            $categoryName = trim(str_replace('"', '', $request->category));
            $category = Category::findByNameStrict($categoryName);
            if (!$category) {
                throw new \Exception('Category not found');
            }

            // Create or update the product
            $product = $request->id
                ? Product::findOrFail($request->id)
                : new Product();

            // Check permissions for updating
            if ($request->id) {
                if (!$user->hasRole('admin') && $product->vendor_id !== $user->id) {
                    throw new \Exception('Unauthorized to modify this product');
                }
            }

            // Set the vendor_id
            $vendorId = $request->input('vendor_id');
            if ($user->hasRole('admin') && $vendorId) {
                // Admin can set any vendor
                $vendor = User::role('supplier')->findOrFail($vendorId);
                $product->vendor_id = $vendor->id;
            } else {
                // Suppliers can only set themselves as vendor
                $product->vendor_id = $user->id;
            }

            $product->fill(array_merge(
                $request->except(['coverUrl', 'images', 'category', 'id', 'vendor_id']),
                ['categoryId' => $category->id, 'available' => $request->quantity]
            ));

            $product->save();

            // Handle both coverUrl and images
            $processImage = function($image, $isPrimary = false) use ($product) {
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

            \Log::info('Product Images after saving', $product->images->toArray());

            DB::commit();
            return new ProductResource($product);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error processing product', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $user = auth()->user();
            $product = Product::findOrFail($id);

            // Check permissions
            if (!$user->hasRole('admin') && $product->vendor_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized to modify this product'], 403);
            }

            // Update publish status if provided
            if ($request->has('publish')) {
                $newStatus = $request->input('publish');
                if (!in_array($newStatus, ['draft', 'published'])) {
                    return response()->json(['message' => 'Invalid publish status'], 400);
                }

                $product->publish = $newStatus;
                $product->save();

                return response()->json([
                    'message' => "Product successfully updated to {$newStatus}",
                    'product' => new ProductResource($product)
                ]);
            }

            // For other updates, use the store method
            $request->merge(['id' => $id]);
            return $this->store($request);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating product', 'error' => $e->getMessage()], 500);
        }
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
}

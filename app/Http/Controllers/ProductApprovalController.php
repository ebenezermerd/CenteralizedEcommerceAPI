<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use App\Mail\ProductApprovalStatus;
use Illuminate\Support\Facades\Mail;


class ProductApprovalController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['reviews', 'category', 'brand', 'images', 'vendor'])
            ->pendingApproval()
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'products' => ProductResource::collection($products),
            'meta' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ]
        ]);
    }

    public function approve(string $id)
    {
        $product = Product::findOrFail($id);
        $product->approve();

        // Send email notification to vendor
        Mail::to($product->vendor->email)
            ->send(new ProductApprovalStatus($product, 'approved'));

        return response()->json([
            'message' => 'Product approved successfully',
            'product' => new ProductResource($product)
        ]);
    }

    public function reject(Request $request, string $id)
    {
        $request->validate([
            'reason' => 'required|string|min:10'
        ]);

        $product = Product::findOrFail($id);
        $product->reject($request->reason);

        // Send email notification to vendor with rejection reason
        Mail::to($product->vendor->email)
            ->send(new ProductApprovalStatus($product, 'rejected', $request->reason));

        return response()->json([
            'message' => 'Product rejected successfully',
            'product' => new ProductResource($product)
        ]);
    }
}

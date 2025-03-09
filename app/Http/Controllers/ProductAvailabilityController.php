<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductAvailabilityController extends Controller
{
    public function checkAvailability(string $productId)
    {
        try {
            $product = Product::findOrFail($productId);

            $response = [
                'available' => $product->available,
                'inventoryType' => $product->inventoryType,
                'status' => $this->getInventoryStatus($product),
                'maxPurchaseQuantity' => $this->getMaxPurchaseQuantity($product)
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Product availability check failed', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to check product availability',
                'available' => 0,
                'status' => 'error'
            ], 500);
        }
    }

    private function getInventoryStatus(Product $product): string
    {
        if ($product->available <= 0) {
            return 'out_of_stock';
        }
        if ($product->available <= 10) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    private function getMaxPurchaseQuantity(Product $product): int
    {
        // Limit max purchase to available quantity or 10, whichever is lower
        return min($product->available, 10);
    }
} 
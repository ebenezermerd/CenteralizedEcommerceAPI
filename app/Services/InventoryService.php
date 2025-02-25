<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function updateInventory(array $items): bool
    {
        try {
            return DB::transaction(function () use ($items) {
                foreach ($items as $item) {
                    $product = Product::find($item['productId']);
                    
                    if (!$product) {
                        throw new \Exception("Product not found: {$item['productId']}");
                    }

                    if ($product->available < $item['quantity']) {
                        throw new \Exception("Insufficient inventory for product: {$product->name}");
                    }

                    $newQuantity = $product->available - $item['quantity'];
                    $newTotalSold = $product->totalSold + $item['quantity'];

                    $product->update([
                        'available' => $newQuantity,
                        'totalSold' => $newTotalSold,
                        'inventoryType' => $this->determineInventoryType($newQuantity)
                    ]);

                    Log::info('Inventory updated', [
                        'product_id' => $product->id,
                        'previous_quantity' => $product->available,
                        'new_quantity' => $newQuantity,
                        'sold' => $item['quantity']
                    ]);
                }

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Inventory update failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function determineInventoryType(int $quantity): string
    {
        if ($quantity <= 0) return 'out_of_stock';
        if ($quantity <= 10) return 'low_stock';
        return 'in_stock';
    }
}
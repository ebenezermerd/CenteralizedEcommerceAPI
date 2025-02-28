<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\Cache;
use App\Events\LowStockEvent;
use App\Models\InventoryReservation;

class InventoryService
{
    // Define inventory status constants
    public const INVENTORY_IN_STOCK = 'in_stock';
    public const INVENTORY_LOW_STOCK = 'low_stock';
    public const INVENTORY_OUT_OF_STOCK = 'out_of_stock';
    public const INVENTORY_DISCONTINUED = 'discontinued';

    // Define threshold for low stock
    private const LOW_STOCK_THRESHOLD = 10;

    private const RESERVATION_TIMEOUT = 900; // 15 minutes

    /**
     * Update inventory for multiple items
     */
    public function updateInventory(array $items): bool
    {
        try {
            return DB::transaction(function () use ($items) {
                foreach ($items as $item) {
                    $product = Product::findOrFail($item['id']);
                    $this->processInventoryUpdate($product, $item['quantity']);
                }
                return true;
            });
        } catch (\Exception $e) {
            Log::error('Inventory update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process inventory update for a single product
     */
    private function processInventoryUpdate(Product $product, int $quantity): void
    {
        if ($product->available < $quantity) {
            throw new InsufficientStockException(
                "Insufficient stock for product: {$product->name}",
                $product
            );
        }

        $newQuantity = $product->available - $quantity;
        $newTotalSold = $product->totalSold + $quantity;

        $product->update([
            'available' => $newQuantity,
            'totalSold' => $newTotalSold,
            'inventoryType' => $this->determineInventoryType($newQuantity)
        ]);

        // Clear cache for this product
        $this->clearProductCache($product);

        // Check if we need to trigger low stock alert
        $this->checkLowStockAlert($product);

        Log::info('Inventory updated', [
            'product_id' => $product->id,
            'previous_quantity' => $product->available + $quantity,
            'new_quantity' => $newQuantity,
            'sold' => $quantity
        ]);
    }

    /**
     * Determine inventory type based on quantity
     */
    public function determineInventoryType(int $quantity): string
    {
        if ($quantity <= 0) {
            return self::INVENTORY_OUT_OF_STOCK;
        }
        if ($quantity <= self::LOW_STOCK_THRESHOLD) {
            return self::INVENTORY_LOW_STOCK;
        }
        return self::INVENTORY_IN_STOCK;
    }

    /**
     * Check and reserve inventory
     */
    public function checkAndReserveInventory(array $items): array
    {
        $failedItems = [];
        $successItems = [];

        foreach ($items as $item) {
            $product = Product::find($item['id']);
            if (!$product || !$this->canFulfillOrder($product, $item['quantity'])) {
                $failedItems[] = [
                    'id' => $item['id'],
                    'name' => $product ? $product->name : 'Unknown Product',
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => $product ? $product->available : 0
                ];
            } else {
                $successItems[] = $item;
            }
        }

        return [
            'success' => empty($failedItems),
            'failed_items' => $failedItems,
            'success_items' => $successItems
        ];
    }

    /**
     * Check if order can be fulfilled
     */
    private function canFulfillOrder(Product $product, int $quantity): bool
    {
        return $product->available >= $quantity;
    }

    /**
     * Clear product cache
     */
    private function clearProductCache(Product $product): void
    {
        Cache::forget("product_availability_{$product->id}");
        Cache::forget("product_{$product->id}");
    }

    /**
     * Check for low stock alert
     */
    private function checkLowStockAlert(Product $product): void
    {
        if ($product->available <= self::LOW_STOCK_THRESHOLD) {
            event(new LowStockEvent($product));
        }
    }

    /**
     * Restock product
     */
    public function restockProduct(Product $product, int $quantity): void
    {
        DB::transaction(function () use ($product, $quantity) {
            $newQuantity = $product->available + $quantity;

            $product->update([
                'available' => $newQuantity,
                'quantity' => $newQuantity,
                'inventoryType' => $this->determineInventoryType($newQuantity)
            ]);

            $this->clearProductCache($product);

            Log::info('Product restocked', [
                'product_id' => $product->id,
                'added_quantity' => $quantity,
                'new_quantity' => $newQuantity
            ]);
        });
    }

    public function reserveInventory(array $items): array
    {
        return DB::transaction(function () use ($items) {
            $reservations = [];
            $failedItems = [];

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item['id']);

                if (!$product || $product->available < $item['quantity']) {
                    $failedItems[] = [
                        'id' => $item['id'],
                        'name' => $product?->name ?? 'Unknown Product',
                        'requested' => $item['quantity'],
                        'available' => $product?->available ?? 0
                    ];
                    continue;
                }

                // Create reservation record
                $reservation = InventoryReservation::create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'expires_at' => now()->addSeconds(self::RESERVATION_TIMEOUT)
                ]);

                // Reduce available quantity
                $product->decrement('available', $item['quantity']);

                $reservations[] = $reservation->id;
            }

            return [
                'success' => empty($failedItems),
                'failed_items' => $failedItems,
                'reservation_ids' => $reservations
            ];
        });
    }
}

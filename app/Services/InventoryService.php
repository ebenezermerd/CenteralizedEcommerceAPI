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

    // Define threshold for low stock - changed to match StockMonitoringService
    private const LOW_STOCK_THRESHOLD = 3; 

    // Increase reservation timeout to 30 minutes
    private const RESERVATION_TIMEOUT = 1800; 

    /**
     * Update inventory for multiple items (used after order confirmation)
     */
    public function updateInventory(array $items): bool
    {
        try {
            return DB::transaction(function () use ($items) {
                foreach ($items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['id']);
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
        // Check available inventory (excluding reservations)
        if ($product->available < $quantity) {
            throw new InsufficientStockException(
                "Insufficient stock for product: {$product->name}",
                $product
            );
        }

        $newAvailable = $product->available - $quantity;
        $newTotalSold = $product->totalSold + $quantity;

        $product->update([
            'available' => $newAvailable,
            'totalSold' => $newTotalSold,
            'inventoryType' => $this->determineInventoryType($newAvailable)
        ]);

        // Clear cache for this product
        $this->clearProductCache($product);

        // Check if we need to trigger low stock alert
        $this->checkLowStockAlert($product);

        Log::info('Inventory updated', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'previous_available' => $product->available + $quantity,
            'new_available' => $newAvailable,
            'sold' => $quantity,
            'total_sold' => $newTotalSold
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
     * Check inventory availability without creating reservations
     */
    public function checkInventoryAvailability(array $items): array
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
        if ($product->available <= self::LOW_STOCK_THRESHOLD && $product->available > 0) {
            event(new LowStockEvent($product));
        }
    }

    /**
     * Restock product
     */
    public function restockProduct(Product $product, int $quantity): void
    {
        DB::transaction(function () use ($product, $quantity) {
            $newQuantity = $product->quantity + $quantity;
            $newAvailable = $product->available + $quantity;

            $product->update([
                'available' => $newAvailable,
                'quantity' => $newQuantity,
                'inventoryType' => $this->determineInventoryType($newAvailable)
            ]);

            $this->clearProductCache($product);

            Log::info('Product restocked', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'added_quantity' => $quantity,
                'new_quantity' => $newQuantity,
                'new_available' => $newAvailable
            ]);
        });
    }

    /**
     * Reserve inventory for checkout process
     */
    public function reserveInventory(array $items): array
    {
        return DB::transaction(function () use ($items) {
            $reservations = [];
            $failedItems = [];

            foreach ($items as $item) {
                // Important: Use lockForUpdate to prevent race conditions
                $product = Product::lockForUpdate()->find($item['id']);

                if (!$product || $product->available < $item['quantity']) {
                    $failedItems[] = [
                        'id' => $item['id'],
                        'name' => $product?->name ?? 'Unknown Product',
                        'requested_quantity' => $item['quantity'],
                        'available_quantity' => $product?->available ?? 0
                    ];
                    continue;
                }

                // Create reservation record
                $reservation = InventoryReservation::create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'session_id' => session()->getId(),
                    'expires_at' => now()->addSeconds(self::RESERVATION_TIMEOUT)
                ]);

                // Reduce available quantity
                $product->decrement('available', $item['quantity']);
                
                // Update inventory type
                $product->update(['inventoryType' => $this->determineInventoryType($product->available)]);

                $reservations[] = $reservation->id;
                
                Log::info('Inventory reserved', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'remaining_available' => $product->available,
                    'reservation_id' => $reservation->id,
                    'expires_at' => $reservation->expires_at
                ]);
            }

            return [
                'success' => empty($failedItems),
                'failed_items' => $failedItems,
                'reservation_ids' => $reservations
            ];
        });
    }
    
    /**
     * Release reserved inventory
     */
    public function releaseReservation(int $reservationId): bool
    {
        return DB::transaction(function () use ($reservationId) {
            $reservation = InventoryReservation::find($reservationId);
            
            if (!$reservation) {
                Log::warning('Attempted to release non-existent reservation', ['id' => $reservationId]);
                return false;
            }
            
            $product = $reservation->product;
            $product->increment('available', $reservation->quantity);
            $product->update(['inventoryType' => $this->determineInventoryType($product->available)]);
            
            $reservation->delete();
            
            Log::info('Reservation released', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $reservation->quantity,
                'new_available' => $product->available
            ]);
            
            return true;
        });
    }
    
    /**
     * Finalize inventory after order completion
     * This converts reservations into actual inventory deductions
     */
    public function finalizeInventory(array $items, array $reservationIds): bool
    {
        return DB::transaction(function () use ($items, $reservationIds) {
            // Release all reservations
            foreach ($reservationIds as $reservationId) {
                $reservation = InventoryReservation::find($reservationId);
                if ($reservation) {
                    $reservation->delete();
                }
            }
            
            // No need to decrement available since it was already reduced during reservation
            // Just update total sold
            foreach ($items as $item) {
                $product = Product::find($item['id']);
                if ($product) {
                    $product->increment('totalSold', $item['quantity']);
                    
                    Log::info('Inventory finalized', [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $item['quantity'],
                        'new_total_sold' => $product->totalSold,
                        'available' => $product->available
                    ]);
                }
            }
            
            return true;
        });
    }
}

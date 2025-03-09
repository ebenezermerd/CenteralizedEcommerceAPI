<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupInventoryReservations extends Command
{
    protected $signature = 'inventory:cleanup-reservations';
    protected $description = 'Clean up expired inventory reservations and return quantities to available stock';

    public function handle()
    {
        $this->info('Starting cleanup of expired inventory reservations...');

        $count = 0;

        DB::transaction(function() use (&$count) {
            InventoryReservation::where('expires_at', '<=', now())
                ->chunk(100, function ($reservations) use (&$count) {
                    foreach ($reservations as $reservation) {
                        $product = $reservation->product;
                        
                        if ($product) {
                            // Return quantity to available stock
                            $product->increment('available', $reservation->quantity);
                            
                            // Update inventory type
                            $inventoryType = $product->available <= 0 ? 'out_of_stock' : 
                                ($product->available <= 3 ? 'low_stock' : 'in_stock');
                            $product->update(['inventoryType' => $inventoryType]);
                            
                            Log::info('Released expired reservation', [
                                'reservation_id' => $reservation->id,
                                'product_id' => $product->id,
                                'product_name' => $product->name,
                                'quantity' => $reservation->quantity,
                                'new_available' => $product->available
                            ]);
                            
                            $count++;
                        }
                        
                        // Delete the reservation
                        $reservation->delete();
                    }
                });
        });

        $this->info("Cleaned up {$count} expired inventory reservations.");
    }
}

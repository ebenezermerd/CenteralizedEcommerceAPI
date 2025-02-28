<?php

namespace App\Console\Commands;

use App\Models\InventoryReservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupInventoryReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:cleanup-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired inventory reservations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            InventoryReservation::cleanupExpired();
            $this->info('Expired inventory reservations cleaned up successfully');
        } catch (\Exception $e) {
            Log::error('Failed to cleanup inventory reservations', [
                'error' => $e->getMessage()
            ]);
            $this->error('Failed to cleanup inventory reservations');
        }
    }
}

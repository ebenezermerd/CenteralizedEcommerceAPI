<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Jobs\SyncContactToHubSpot;
use App\Jobs\SyncProductToHubSpot;
use App\Jobs\SyncOrderToHubSpot;

class HubSpotSyncAll extends Command
{
    protected $signature = 'hubspot:sync-all';
    protected $description = 'Sync all Users, Products, and Orders to HubSpot';

    public function handle()
    {
        $this->info('Syncing Users...');
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                SyncContactToHubSpot::dispatch($user);
            }
        });

        $this->info('Syncing Products...');
        Product::chunk(100, function ($products) {
            foreach ($products as $product) {
                SyncProductToHubSpot::dispatch($product);
            }
        });

        $this->info('Syncing Orders...');
        Order::chunk(100, function ($orders) {
            foreach ($orders as $order) {
                SyncOrderToHubSpot::dispatch($order);
            }
        });

        $this->info('All sync jobs dispatched!');
    }
}

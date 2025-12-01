<?php

namespace App\Observers;

use App\Models\Product;
use App\Jobs\SyncProductToHubSpot;

class ProductObserver
{
    public function created(Product $product)
    {
        SyncProductToHubSpot::dispatch($product);
    }

    public function updated(Product $product)
    {
        SyncProductToHubSpot::dispatch($product);
    }
}

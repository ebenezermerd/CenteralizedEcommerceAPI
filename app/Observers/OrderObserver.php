<?php

namespace App\Observers;

use App\Models\Order;
use App\Jobs\SyncOrderToHubSpot;

class OrderObserver
{
    public function created(Order $order)
    {
        SyncOrderToHubSpot::dispatch($order);
    }

    public function updated(Order $order)
    {
        SyncOrderToHubSpot::dispatch($order);
    }
}

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inventory Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for inventory management
    */

    // Low stock threshold - products with stock below this will trigger alerts
    'low_stock_threshold' => env('INVENTORY_LOW_STOCK_THRESHOLD', 3),
    
    // Reservation timeout in seconds (30 minutes)
    'reservation_timeout' => env('INVENTORY_RESERVATION_TIMEOUT', 1800),
    
    // Maximum purchase quantity per order
    'max_purchase_quantity' => env('INVENTORY_MAX_PURCHASE_QUANTITY', 10),
    
    // Inventory status types
    'status_types' => [
        'in_stock' => 'In Stock',
        'low_stock' => 'Low Stock',
        'out_of_stock' => 'Out of Stock',
        'discontinued' => 'Discontinued'
    ],
];
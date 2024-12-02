<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = User::factory(10)->create();

        Order::factory(50)
            ->recycle($users)
            ->create();
        
        // Create 10 products
        $products = Product::factory(10)->create();

        // Create 50 orders, reusing the created users and products
        Order::factory(50)
            ->recycle($users)
            ->recycle($products)
            ->create();
    }
}

<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Will be overridden by recycle in seeder
            'product_id' => Product::factory(), // Will be overridden by recycle in seeder
            'taxes' => fake()->randomFloat(2, 1, 100),
            'status' => fake()->randomElement(['pending', 'completed', 'declined']),
            'quantity' => fake()->randomFloat(2, 1, 10),
            'shipping' => fake()->randomFloat(2, 5, 50),
            'discount' => fake()->randomFloat(2, 0, 100),
            'subtotal' => fake()->randomFloat(2, 100, 1000),
            'order_number' => fake()->unique()->numerify('ORD-#####'),
            'total_amount' => fake()->randomFloat(2, 100, 2000),
            'total_quantity' => fake()->numberBetween(1, 10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

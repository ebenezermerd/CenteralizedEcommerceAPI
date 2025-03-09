<?php

namespace Database\Factories;

use App\Models\ProductReview;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->name,
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->paragraph,
            'helpful' => $this->faker->numberBetween(0, 10000),
            'avatar_url' => 'https://api-prod-minimal-v620.pages.dev/assets/images/avatar/avatar-' . $this->faker->numberBetween(1, 8) . '.webp',
            'posted_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'is_purchased' => $this->faker->boolean(70),
            'attachments' => $this->faker->boolean(30) ? [
                'https://api-prod-minimal-v620.pages.dev/assets/images/m-product/product-' . $this->faker->numberBetween(1, 8) . '.webp'
            ] : []
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku' => $this->faker->unique()->numerify('SKU-#####'),
            'name' => $this->faker->word,
            'code' => $this->faker->unique()->numerify('CODE-#####'),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'taxes' => $this->faker->randomFloat(2, 1, 100),
            'tags' => $this->faker->words(5),
            'sizes' => $this->faker->randomElements(['S', 'M', 'L', 'XL'], 2),
            'publish' => $this->faker->boolean ? 'yes' : 'no',
            'gender' => $this->faker->randomElements(['male', 'female', 'unisex'], 2),
            'cover_url' => $this->faker->imageUrl(),
            'images' => [
                $this->faker->imageUrl(), 
                $this->faker->imageUrl()
            ], // Fixed to return an array of image URLs
            'colors' => $this->faker->randomElements(['red', 'blue', 'green', 'yellow'], 2),
            'quantity' => $this->faker->numberBetween(1, 100),
            'category' => $this->faker->word,
            'available' => $this->faker->boolean,
            'total_sold' => $this->faker->numberBetween(0, 1000),
            'description' => $this->faker->paragraph,
            'total_ratings' => $this->faker->numberBetween(0, 5),
            'total_reviews' => $this->faker->numberBetween(0, 100),
            'inventory_type' => $this->faker->word,
            'sub_description' => $this->faker->paragraph,
            'price_sale' => $this->faker->optional()->randomFloat(2, 5, 500),
            'sale_label' => $this->faker->words(3),
            'new_label' => $this->faker->words(3),
        ];
    }
}

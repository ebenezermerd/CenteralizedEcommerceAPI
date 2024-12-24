<?php

namespace Database\Factories;

use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        $imageNumber = $this->faker->numberBetween(1, 8);
        return [
            'image_path' => "products/product-{$imageNumber}.webp",
            'is_primary' => false
        ];
    }

    public function primary()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_primary' => true
            ];
        });
    }

    public function additional()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_primary' => false
            ];
        });
    }

    public function forProduct($product)
    {
        return $this->state(function (array $attributes) use ($product) {
            return [
                'product_id' => $product->id,
                'product' => $product
            ];
        });
    }
}

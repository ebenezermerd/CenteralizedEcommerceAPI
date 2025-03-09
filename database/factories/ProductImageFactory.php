<?php

namespace Database\Factories;

use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
         // Get all image files from the products directory
         $imageFiles = Storage::disk('public')->files('products');

         // Randomly select an image file
         $imagePath = $this->faker->randomElement($imageFiles);

         return [
             'image_path' => $imagePath,
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

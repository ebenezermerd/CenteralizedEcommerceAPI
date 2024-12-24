<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Clear old product images
        Storage::disk('public')->deleteDirectory('products');
        Storage::disk('public')->makeDirectory('products');

        // Base64 encoded 1x1 pixel transparent PNG
        $placeholderImage = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');

        // Create products
        Product::factory()
            ->count(50)
            ->create()
            ->each(function ($product) use ($placeholderImage) {
                // Create 3-5 images per product
                $imageCount = rand(3, 5);
                
                for ($i = 0; $i < $imageCount; $i++) {
                    $imagePath = "products/{$product->id}/image{$i}.png";
                    
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'is_primary' => $i === 0
                    ]);
                    
                    Storage::disk('public')->put($imagePath, $placeholderImage);
                }
            });
    }
}

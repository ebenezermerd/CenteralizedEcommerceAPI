<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

    public function run(): void
    {
        // Get all suppliers
        $suppliers = User::role('supplier')->get();

        if ($suppliers->isEmpty()) {
            throw new \Exception('No suppliers found. Please run UserSeeder first.');
        }

        // Clear existing data
        Product::query()->delete();
        ProductImage::query()->delete();

        // Clear old images and recreate directory
        Storage::disk('public')->deleteDirectory('products');
        Storage::disk('public')->makeDirectory('products');

        // Copy seed images to storage
        $seedImagesPath = database_path('seeders/images');
        $seedImages = glob("$seedImagesPath/*.{jpg,jpeg,png,gif}", GLOB_BRACE);

        // Validate seed images exist
        if (empty($seedImages)) {
            throw new \Exception("No images found in: $seedImagesPath");
        }

        foreach ($seedImages as $image) {
            $imageName = basename($image);
            Storage::disk('public')->put("products/$imageName", file_get_contents($image));
        }

        // Get copied image paths
        $imageFiles = Storage::disk('public')->files('products');

        // Validate copied images
        if (empty($imageFiles)) {
            throw new \Exception("Failed to copy images to storage/public/products");
        }

        // Reset Faker uniqueness
        $this->faker->unique(true);

        // Create products for each supplier
        foreach ($suppliers as $supplier) {
            Product::factory(rand(5, 10))->create([
                'vendor_id' => $supplier->id
            ])->each(function ($product) use ($imageFiles) {
                // Primary image
                $primaryImagePath = $this->faker->randomElement($imageFiles);
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $primaryImagePath,
                    'is_primary' => true
                ]);
                $product->update(['coverUrl' => $primaryImagePath]);

                // Additional images (3-5)
                $additionalImageCount = rand(3, 5);
                for ($i = 0; $i < $additionalImageCount; $i++) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $this->faker->randomElement($imageFiles),
                        'is_primary' => false
                    ]);
                }
            });
        }
    }
}

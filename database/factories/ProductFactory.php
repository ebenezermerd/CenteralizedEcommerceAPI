<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    protected static $productsByCategory = [
        'Clothing' => [
            'name' => ['Shirts', 'T-shirts', 'Jeans', 'Leather Jacket', 'Dresses'],
            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'colors' => ['#FF4842', '#1890FF', '#00AB55', '#FFC107', '#7F00FF', '#000000'],
            'gender' => ['Men', 'Women', 'Kids']
        ],
        'Tailored' => [
            'name' => ['Business Suit', 'Dress Shirt', 'Wool Blazer', 'Formal Trousers', 'Tailored Vest'],
            'sizes' => ['46', '48', '50', '52', '54'],
            'colors' => ['#000000', '#2065D1', '#212B36', '#919EAB'],
            'gender' => ['Men', 'Women']
        ],
        'Accessories' => [
            'name' => ['Watches', 'Backpacks', 'Bracelets', 'Face Masks', 'Sunglasses', 'Belts'],
            'sizes' => ['small', 'medium', 'large'],
            'colors' => ['#FF4842', '#1890FF', '#54D62C', '#FFC107', '#7F00FF'],
            'gender' => ['Men', 'Women', 'Kids']
        ],
        'Shoes' => [
            'name' => ['Athletic Shoes', 'Formal Shoes', 'Sandals', 'Boots', 'Sneakers'],
            'sizes' => ['7', '8', '8.5', '9', '9.5', '10', '10.5', '11', '11.5', '12', '13'],
            'colors' => ['#000000', '#FFFFFF', '#FF4842', '#1890FF', '#00AB55'],
            'gender' => ['Men', 'Women', 'Kids']
        ],
        'Bags' => [
            'name' => ['Backpacks', 'Handbags', 'Briefcases', 'Tote Bags', 'Travel Bags'],
            'sizes' => ['small', 'medium', 'large'],
            'colors' => ['#000000', '#2065D1', '#54D62C', '#FFC107'],
            'gender' => ['Men', 'Women']
        ],
        'Electronics' => [
            'name' => ['Smartphones', 'Laptops', 'Headphones', 'Smart Watches', 'Tablets'],
            'sizes' => ['OS'],
            'colors' => ['#000000', '#FFFFFF', '#919EAB'],
            'gender' => ['Unisex']
        ]
    ];

    public function definition(): array
    {
        $category = Category::inRandomOrder()->first();

        // Get a random supplier
        $vendor = User::role('supplier')->inRandomOrder()->first();
        if (!$vendor) {
            // If no supplier exists, create one
            $vendor = User::factory()->create();
            $vendor->assignRole('supplier');
        }

        $categoryData = self::$productsByCategory[$category->group] ?? [
            'name' => ['Generic Product'],
            'sizes' => ['OS'],
            'colors' => ['#000000'],
            'gender' => ['Unisex']
        ];

        $quantity = $this->faker->numberBetween(0, 100);
        $totalSold = $this->faker->numberBetween(0, 1000);
        $isOnSale = $this->faker->boolean(30);
        $basePrice = $this->faker->randomFloat(2, 20, 500);

        return [
            'vendor_id' => $vendor->id,
            'categoryId' => $category->id,
            'name' => $this->faker->randomElement($categoryData['name']),
            'sku' => $this->faker->unique()->numerify('WW75K5####YW/SV'),
            'code' => $this->faker->unique()->bothify('38BEE###'), // Changed from numerify()
            'description' => $this->generateDescription(),
            'subDescription' => $this->faker->sentence(),
            'publish' => $this->faker->randomElement(['draft', 'published']),

            // Pricing
            'price' => $basePrice,
            'priceSale' => $isOnSale ? $basePrice * 0.8 : null,
            'taxes' => 10,

            // Media

            // Attributes
            'tags' => $this->faker->randomElements([
                'Technology',
                'Health and Wellness',
                'Travel',
                'Finance',
                'Education',
                'Food and Beverage',
                'Fashion',
                'Home and Garden',
                'Sports',
                'Entertainment',
                'Business',
                'Science',
                'Automotive',
                'Beauty',
                'Fitness',
                'Lifestyle',
                'Real Estate',
                'Parenting',
                'Pet Care',
                'Environmental',
                'DIY and Crafts',
                'Gaming',
                'Photography',
                'Music'
            ], 2),
            'sizes' => $categoryData['sizes'],
            'colors' => $categoryData['colors'],
            'gender' => $categoryData['gender'],

            // Inventory
            'inventoryType' => $quantity <= 0 ? 'out_of_stock' : ($quantity <= 10 ? 'low_stock' : 'in_stock'),
            'quantity' => $quantity,
            'available' => $quantity,
            'totalSold' => $totalSold,

            // Ratings and Reviews
            'totalRatings' => $this->faker->randomFloat(1, 0, 5),
            'totalReviews' => $this->faker->numberBetween(0, 500),

            // Labels
            'newLabel' => $this->faker->boolean(20) ? [
                'enabled' => true,
                'content' => 'NEW'
            ] : null,
            'saleLabel' => $isOnSale ? [
                'enabled' => true,
                'content' => 'SALE'
            ] : null,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Product $product) {
            // Get available images from storage
            $imageFiles = Storage::disk('public')->files('products');

            if (!empty($imageFiles)) {
                // Create primary image
                $primaryImagePath = $this->faker->randomElement($imageFiles);
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $primaryImagePath,
                    'is_primary' => true
                ]);
                $product->update(['coverUrl' => $primaryImagePath]);

                // Create 3-5 additional images
                $additionalImageCount = rand(3, 5);
                for ($i = 0; $i < $additionalImageCount; $i++) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $this->faker->randomElement($imageFiles),
                        'is_primary' => false
                    ]);
                }
            }
        });
    }

    private function generateDescription(): string
    {
        return <<<HTML
<h6>Specifications</h6>
<table>
  <tbody>
    <tr>
      <td>Category</td>
      <td>{$this->faker->word}</td>
    </tr>
    <tr>
      <td>Manufacturer</td>
      <td>{$this->faker->company}</td>
    </tr>
    <tr>
      <td>Serial number</td>
      <td>{$this->faker->ean13}</td>
    </tr>
    <tr>
      <td>Ships From</td>
      <td>{$this->faker->country}</td>
    </tr>
  </tbody>
</table>
HTML;
    }
}

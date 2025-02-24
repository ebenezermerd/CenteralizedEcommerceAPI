<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use App\Models\Brand;
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
<<<<<<< HEAD
            'name' => ['Watches', 'Backpacks', 'Face Masks', 'Sunglasses', 'Belts'],
=======
            'name' => ['Watches', 'Bracelets', 'Face Masks', 'Sunglasses', 'Belts'],
>>>>>>> 589c26518b29f48f1da1b142b1287bae2efc4947
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


    protected $categoryBrands = [
        'Shirts' => [
            ['name' => 'Tommy Hilfiger', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d2/Tommy_Hilfiger_Logo.svg/1280px-Tommy_Hilfiger_Logo.svg.png'],
            ['name' => 'Calvin Klein', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Calvin_Klein_logo.svg/1280px-Calvin_Klein_logo.svg.png'],
            ['name' => 'H&M', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/53/H%26M-Logo.svg']
        ],
        'Tailored' => [
            ['name' => 'Hugo Boss', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d2/Hugo_Boss_Logo.svg/1280px-Hugo_Boss_Logo.svg.png'],
            ['name' => 'Zara', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Zara_Logo.svg/1280px-Zara_Logo.svg.png'],
            ['name' => 'H&M', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/53/H%26M-Logo.svg']
        ],
        'Clothing' => [
            ['name' => 'Tommy Hilfiger', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d2/Tommy_Hilfiger_Logo.svg/1280px-Tommy_Hilfiger_Logo.svg.png'],
            ['name' => 'Calvin Klein', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Calvin_Klein_logo.svg/1280px-Calvin_Klein_logo.svg.png'],
            ['name' => 'H&M', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/53/H%26M-Logo.svg']
        ],

        'Electronics' => [
            ['name' => 'Samsung', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/24/Samsung_Logo.svg'],
            ['name' => 'Apple', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg'],
            ['name' => 'Sony', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/36/Sony_logo.svg/1280px-Sony_logo.svg.png']
        ],
        'Smartphones' => [
            ['name' => 'Xiaomi', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Xiaomi_Logo.svg/1280px-Xiaomi_Logo.svg.png'],
            ['name' => 'Huawei', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Xiaomi_Logo.svg/1280px-Xiaomi_Logo.svg.png'],
            ['name' => 'Oppo', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Xiaomi_Logo.svg/1280px-Xiaomi_Logo.svg.png']
        ],
        'Sports & Outdoors' => [
            ['name' => 'Nike', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Adidas_Logo.svg/1280px-Adidas_Logo.svg.png'],
            ['name' => 'Adidas', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Adidas_Logo.svg/1280px-Adidas_Logo.svg.png'],
            ['name' => 'Puma', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/36/Puma_Logo.svg/1280px-Puma_Logo.svg.png']
        ],
        'Home & Kitchen' => [
            ['name' => 'IKEA', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/IKEA_logo.svg/1280px-IKEA_logo.svg.png'],
            ['name' => 'Tefal', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/IKEA_logo.svg/1280px-IKEA_logo.svg.png'],
            ['name' => 'Philips', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/IKEA_logo.svg/1280px-IKEA_logo.svg.png']
        ],
        'Beauty & Personal Care' => [
            ['name' => 'L\'Oréal', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/LOr%C3%A9al_logo.svg/1280px-LOr%C3%A9al_logo.svg.png'],
            ['name' => 'Dove', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/26/Dove_logo.svg/1280px-Dove_logo.svg.png'],
            ['name' => 'Nivea', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/75/Nivea_Logo_2005.svg/1280px-Nivea_Logo_2005.svg.png']
        ],
        'Accessories' => [
            ['name' => 'Rolex', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Rolex_logo.svg/1280px-Rolex_logo.svg.png'],
            ['name' => 'Cartier', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c7/Cartier_logo.svg/1280px-Cartier_logo.svg.png'],
            ['name' => 'Omega', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Omega_logo.svg/1280px-Omega_logo.svg.png']
        ],
        'Shoes' => [
            ['name' => 'Adidas', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Adidas_Logo.svg/1280px-Adidas_Logo.svg.png'],
            ['name' => 'Nike', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/a/a6/Logo_NIKE.svg'],
            ['name' => 'Puma', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/36/Puma_Logo.svg/1280px-Puma_Logo.svg.png']
        ],
        'Bags' => [
            ['name' => 'Louis Vuitton', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/47/Louis_Vuitton_logo.svg/1280px-Louis_Vuitton_logo.svg.png'],
            ['name' => 'Chanel', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c7/Chanel_logo.svg/1280px-Chanel_logo.svg.png'],
            ['name' => 'Gucci', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3c/Gucci_logo.svg/1280px-Gucci_logo.svg.png']
        ],
        'Jewelry' => [
            ['name' => 'Tiffany & Co.', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Tiffany_&_Co._logo.svg/1280px-Tiffany_&_Co._logo.svg.png'],
            ['name' => 'Cartier', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c7/Cartier_logo.svg/1280px-Cartier_logo.svg.png'],
            ['name' => 'Omega', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Omega_logo.svg/1280px-Omega_logo.svg.png']
        ],
        'Toys & Games' => [
            ['name' => 'Lego', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/LEGO_logo.svg/1280px-LEGO_logo.svg.png'],
            ['name' => 'Hasbro', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/36/Hasbro_logo.svg/1280px-Hasbro_logo.svg.png'],
            ['name' => 'Mattel', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/36/Mattel_logo.svg/1280px-Mattel_logo.svg.png']
        ],
        'Art & Collectibles' => [
            ['name' => 'Van Gogh', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Van_Gogh_logo.svg/1280px-Van_Gogh_logo.svg.png'],
            ['name' => 'Picasso', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/36/Picasso_logo.svg/1280px-Picasso_logo.svg.png'],
            ['name' => 'Monet', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Monet_logo.svg/1280px-Monet_logo.svg.png']
        ],
        'Automotive' => [
            ['name' => 'Mercedes-Benz', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Mercedes-Benz_logo.svg/1280px-Mercedes-Benz_logo.svg.png'],
            ['name' => 'BMW', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b4/BMW_logo_%28gray%29.svg/1280px-BMW_logo_%28gray%29.svg.png'],
            ['name' => 'Audi', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/61/Audi_logo_black.svg/1280px-Audi_logo_black.svg.png']
        ],
    ];

    public function definition(): array
    {
        $category = Category::inRandomOrder()->first();
        $brand = Brand::inRandomOrder()->first();

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

        $brandData = self::$categoryBrands[$brand->group] ?? [
            'name' => ['Generic Brand'],
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Van_Gogh_logo.svg/1280px-Van_Gogh_logo.svg.png'
        ];


        $quantity = $this->faker->numberBetween(0, 100);
        $totalSold = $this->faker->numberBetween(0, 1000);
        $isOnSale = $this->faker->boolean(30);
        $basePrice = $this->faker->randomFloat(2, 20, 500);

        return [
            'vendor_id' => $vendor->id,
            'categoryId' => $category->id,
            'brandId' => $brand->id,
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

<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    protected static $productsByCategory = [
        'Clothing' => [
            'name' => ['Classic T-Shirt', 'Denim Jeans', 'Leather Jacket', 'Cotton Shirt', 'Wool Sweater'],
            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'colors' => ['#FF4842', '#1890FF', '#54D62C', '#FFC107', '#00AB55'],
            'gender' => ['Men', 'Women', 'Kids']
        ],
        'Tailored' => [
            'name' => ['Business Suit', 'Dress Shirt', 'Wool Blazer', 'Formal Trousers', 'Tailored Vest'],
            'sizes' => ['46', '48', '50', '52', '54'],
            'colors' => ['#000000', '#2065D1', '#212B36', '#919EAB'],
            'gender' => ['Men', 'Women']
        ],
        'Accessories' => [
            'name' => ['Leather Belt', 'Silk Tie', 'Watch', 'Sunglasses', 'Wallet'],
            'sizes' => ['OS'],
            'colors' => ['#FF4842', '#1890FF', '#54D62C', '#FFC107'],
            'gender' => ['Kids']
        ]
    ];

    public function definition(): array
    {
        $category = Category::inRandomOrder()->first();
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
            'categoryId' => $category->id,
            'name' => $this->faker->randomElement($categoryData['name']),
            'sku' => $this->faker->unique()->numerify('WW75K5####YW/SV'),
            'code' => $this->faker->unique()->numerify('38BEE###'),
            'description' => $this->generateDescription(),
            'subDescription' => $this->faker->sentence(),
            'publish' => $this->faker->randomElement(['draft', 'published']),

            // Pricing
            'price' => $basePrice,
            'priceSale' => $isOnSale ? $basePrice * 0.8 : null,
            'taxes' => 10,

            // Media
            'coverUrl' => 'products/default.jpg',

            // Attributes
            'tags' => $this->faker->randomElements(['Technology', 'Marketing', 'Design', 'Photography', 'Art'], 2),
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

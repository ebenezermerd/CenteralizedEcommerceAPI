<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class CategorySeeder extends Seeder
{
    protected $categories = [
        [
            [
                'group' => 'Clothing',
                'classify' => ['Shirts', 'T-shirts', 'Jeans', 'Leather Jackets', 'Traditional Wear', 'Activewear', 'Underwear', 'Swimwear'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/3144/3144456.png' // Consider replacing with a colorful clothing image
            ],
            [
                'group' => 'Tailored',
                'classify' => ['Suits', 'Tuxedos', 'Dresses', 'Tops', 'Bottoms', 'Accessories'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2790/2790370.png' // New tailored clothing icon
            ],
            [
                'group' => 'Electronics',
                'classify' => ['Smartphones', 'Laptops', 'Tablets', 'Cameras', 'Headphones', 'Smart Watches', 'Home Appliances'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/1067/1067555.png' // Consider a colorful electronics image
            ],
            [
                'group' => 'Home & Kitchen',
                'classify' => ['Furniture', 'Cookware', 'Tableware', 'Home Decor', 'Bedding', 'Storage', 'Lighting'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2379/2379675.png' // Consider a colorful home decor image
            ],
            [
                'group' => 'Beauty & Personal Care',
                'classify' => ['Skincare', 'Haircare', 'Makeup', 'Fragrances', 'Men\'s Grooming', 'Oral Care'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/3082/3082019.png' // Consider a colorful makeup image
            ],
            [
                'group' => 'Sports & Outdoors',
                'classify' => ['Fitness Equipment', 'Camping Gear', 'Cycling', 'Team Sports', 'Yoga', 'Fishing'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/857/857455.png' // Consider a colorful sports image
            ],
            [
                'group' => 'Groceries',
                'classify' => ['Beverages', 'Snacks', 'Canned Goods', 'Bakery', 'Dairy', 'Coffee & Tea'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png' // Consider a colorful grocery basket image
            ],
            [
                'group' => 'Accessories',
                'classify' => ['Watches', 'Jewelry', 'Earrings', 'Necklaces', 'Rings',  'Bracelets', 'Face Masks', 'Sunglasses', 'Belts', 'Backpacks', 'Handbags', 'Briefcases', 'Tote Bags', 'Travel Bags'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/1828/1828861.png' // New jewelry icon
            ],
            [
                'group' => 'Shoes',
                'classify' => ['Athletic Shoes', 'Formal Shoes', 'Sandals', 'Boots', 'Sneakers'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png' // Consider a colorful shoe image
            ],
            [
                'group' => 'Toys & Games',
                'classify' => ['Puzzles', 'Dolls', 'Action Figures', 'Remote-Controlled Toys', 'Strollers'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png' // Consider a colorful toy image
            ],
            [
                'group' => 'Art & Collectibles',
                'classify' => ['Paintings', 'Sculptures', 'Posters', 'Antiques', 'Vintage Items'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png' // Consider a colorful art image
            ],
            [
                'group' => 'Automotive',
                'classify' => ['Car Parts', 'Car Accessories', 'Car Care', 'Car Modifications', 'Car Tuning'],
                'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png' // Consider a colorful car image
            ],
        ]

    ];



    public function run(): void
    {
        foreach ($this->categories as $categories) {
            // Create main category
            $mainCategory = Category::updateOrCreate(
                ['name' => $categories['group']],
                [
                    'name' => $categories['group'],
                    'group' => $categories['group'],
                    'description' => "Main category for {$categories['group']}",
                    'coverImg' => $categories['coverImg'] ?? null,
                    'isActive' => true
                ]
            );

            // Create sub-categories
            foreach ($categories['classify'] as $subCategory) {
                Category::updateOrCreate(
                    [
                        'name' => $subCategory,
                        'parentId' => $mainCategory->id
                    ],
                    [
                        'name' => $subCategory,
                        'group' => $categories['group'],
                        'description' => "{$subCategory} in {$categories['group']} category",
                        'isActive' => true
                    ]
                );
            }

            Log::channel('categories')->info('Category created', [
                'category_id' => $mainCategory->id,
                'user_id' => auth()->id()
            ]);
        }
    }
}



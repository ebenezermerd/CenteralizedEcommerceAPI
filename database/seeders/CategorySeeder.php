<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class CategorySeeder extends Seeder
{
    protected $categories = [
        [
            'group' => 'Clothing',
            'classify' => ['Shirts', 'T-shirts', 'Jeans', 'Leather Jackets', 'Traditional Wear', 'Activewear', 'Underwear', 'Swimwear'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/3144/3144456.png'
        ],
        [
            'group' => 'Electronics',
            'classify' => ['Smartphones', 'Laptops', 'Tablets', 'Cameras', 'Headphones', 'Smart Watches', 'Home Appliances'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/1067/1067555.png'
        ],
        [
            'group' => 'Home & Kitchen',
            'classify' => ['Furniture', 'Cookware', 'Tableware', 'Home Decor', 'Bedding', 'Storage', 'Lighting'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2379/2379675.png'
        ],
        [
            'group' => 'Beauty & Personal Care',
            'classify' => ['Skincare', 'Haircare', 'Makeup', 'Fragrances', 'Men\'s Grooming', 'Oral Care'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/3082/3082019.png'
        ],
        [
            'group' => 'Sports & Outdoors',
            'classify' => ['Fitness Equipment', 'Camping Gear', 'Cycling', 'Team Sports', 'Yoga', 'Fishing'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/857/857455.png'
        ],
        [
            'group' => 'Groceries',
            'classify' => ['Beverages', 'Snacks', 'Canned Goods', 'Bakery', 'Dairy', 'Coffee & Tea'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png'
        ],
        [
            'group' => 'Accessories',
            'classify' => ['Watches', 'Backpacks', 'Bracelets', 'Face Masks', 'Sunglasses', 'Belts'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png'
        ],
        [
            'group' => 'Shoes',
            'classify' => ['Athletic Shoes', 'Formal Shoes', 'Sandals', 'Boots', 'Sneakers'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png'
        ],
        [
            'group' => 'Bags',
            'classify' => ['Backpacks', 'Handbags', 'Briefcases', 'Tote Bags', 'Travel Bags'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png'
        ],
        [
            'group' => 'Jewelry',
            'classify' => ['Necklaces', 'Earrings', 'Bracelets', 'Rings', 'Watches'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png'
        ],
        [
            'group' => 'Toys & Games',
            'classify' => ['Puzzles', 'Dolls', 'Action Figures', 'Remote-Controlled Toys', 'Strollers'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png'
        ],
        [
            'group' => 'Art & Collectibles',
            'classify' => ['Paintings', 'Sculptures', 'Posters', 'Antiques', 'Vintage Items'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png'
        ],
        [
            'group' => 'Automotive',
            'classify' => ['Car Parts', 'Car Accessories', 'Car Care', 'Car Modifications', 'Car Tuning'],
            'coverImg' => 'https://cdn-icons-png.flaticon.com/512/2553/2553992.png'
        ],

    ];



    public function run(): void
    {
        foreach ($this->categories as $category) {
            // Create main category
            $mainCategory = Category::updateOrCreate(
                ['name' => $category['group']],
                [
                    'name' => $category['group'],
                    'group' => $category['group'],
                    'description' => "Main category for {$category['group']}",
                    'coverImg' => $category['coverImg'] ?? null,
                    'isActive' => true
                ]
            );

            // Create sub-categories
            foreach ($category['classify'] as $subCategory) {
                Category::updateOrCreate(
                    [
                        'name' => $subCategory,
                        'parentId' => $mainCategory->id
                    ],
                    [
                        'name' => $subCategory,
                        'group' => $category['group'],
                        'description' => "{$subCategory} in {$category['group']} category",
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



<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    protected $categories = [
        [
            'group' => 'Clothing',
            'classify' => ['Shirts', 'T-shirts', 'Jeans', 'Leather', 'Accessories']
        ],
        [
            'group' => 'Tailored',
            'classify' => ['Suits', 'Blazers', 'Trousers', 'Waistcoats', 'Apparel']
        ],
        [
            'group' => 'Accessories',
            'classify' => ['Shoes', 'Backpacks and bags', 'Bracelets', 'Face masks']
        ]
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
        }
    }
}

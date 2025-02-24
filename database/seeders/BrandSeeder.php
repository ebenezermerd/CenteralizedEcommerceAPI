<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run()
    {
        $brandsByCategory = [
            'Shirts' => [
                ['name' => 'Tommy Hilfiger', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d2/Tommy_Hilfiger_Logo.svg/1280px-Tommy_Hilfiger_Logo.svg.png'],
                ['name' => 'Calvin Klein', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Calvin_Klein_logo.svg/1280px-Calvin_Klein_logo.svg.png'],
                ['name' => 'H&M', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/53/H%26M-Logo.svg']
            ],
            'Electronics' => [
                ['name' => 'Samsung', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/24/Samsung_Logo.svg'],
                ['name' => 'Apple', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg'],
                ['name' => 'Sony', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/4/4e/Sony_logo_%282017%29.svg']
            ],
            'Smartphones' => [
                ['name' => 'Xiaomi', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/29/Xiaomi_logo.svg'],
                ['name' => 'Huawei', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/59/Huawei_Standard_logo.svg'],
                ['name' => 'Oppo', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/1/1e/Oppo_LOGO_%282019%29.svg']
            ],
            'Sports & Outdoors' => [
                ['name' => 'Nike', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/a/a6/Logo_NIKE.svg'],
                ['name' => 'Adidas', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/20/Adidas_Logo.svg'],
                ['name' => 'Puma', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/3/36/Puma_Logo.svg']
            ],
            'Home & Kitchen' => [
                ['name' => 'IKEA', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/1/1b/Ikea-logo.png'],
                ['name' => 'Tefal', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/7/7c/Tefal_logo.svg'],
                ['name' => 'Philips', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/3/33/Philips_Wordmark.svg']
            ],
            'Beauty & Personal Care' => [
                ['name' => 'L\'Oréal', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/8/89/LOr%C3%A9al_logo.svg'],
                ['name' => 'Dove', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/26/Dove_logo.svg'],
                ['name' => 'Nivea', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/7/75/Nivea_Logo_2005.svg']
            ],
        ];

        foreach ($brandsByCategory as $categoryName => $brands) {
            $category = Category::where('name', $categoryName)->first();
            if (!$category) continue;

            foreach ($brands as $brandData) {
                $brand = Brand::firstOrCreate(
                    ['name' => $brandData['name']],
                    [
                        'description' => "{$brandData['name']} official brand",
                        'logo' => $brandData['logo'] ?? null
                    ]
                );
                $category->brands()->attach($brand->id);
            }
        }
    }
} 
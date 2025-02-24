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
            'Accessories' => [
                ['name' => 'Rolex', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Rolex_logo.svg/1280px-Rolex_logo.svg.png'],
                ['name' => 'Cartier', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c7/Cartier_logo.svg/1280px-Cartier_logo.svg.png'],
                ['name' => 'Omega', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Omega_logo.svg/1280px-Omega_logo.svg.png']
            ],
            'Shoes' => [
                ['name' => 'Adidas', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/20/Adidas_Logo.svg'],
                ['name' => 'Nike', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/a/a6/Logo_NIKE.svg'],
                ['name' => 'Puma', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/3/36/Puma_Logo.svg']
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

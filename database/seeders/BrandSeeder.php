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
            [
                'Shirts' => [
                    ['name' => 'Tommy Hilfiger', 'logo' => 'https://icon.horse/icon/www.tommy.com'],
                    ['name' => 'Calvin Klein', 'logo' => 'https://icon.horse/icon/www.calvinklein.com'],
                    ['name' => 'H&M', 'logo' => 'https://icon.horse/icon/www.hm.com']
                ],
                'Tailored' => [
                    ['name' => 'Hugo Boss', 'logo' => 'https://icon.horse/icon/www.hugoboss.com'],
                    ['name' => 'Zara', 'logo' => 'https://icon.horse/icon/www.zara.com'],
                    ['name' => 'H&M', 'logo' => 'https://icon.horse/icon/www.hm.com']
                ],
                'Electronics' => [
                    ['name' => 'Samsung', 'logo' => 'https://icon.horse/icon/www.samsung.com'],
                    ['name' => 'Apple', 'logo' => 'https://icon.horse/icon/www.apple.com'],
                    ['name' => 'Sony', 'logo' => 'https://icon.horse/icon/www.sony.com']
                ],
                'Smartphones' => [
                    ['name' => 'Xiaomi', 'logo' => 'https://icon.horse/icon/www.mi.com'],
                    ['name' => 'Huawei', 'logo' => 'https://icon.horse/icon/www.huawei.com'],
                    ['name' => 'Oppo', 'logo' => 'https://icon.horse/icon/www.oppo.com']
                ],
                'Sports & Outdoors' => [
                    ['name' => 'Nike', 'logo' => 'https://icon.horse/icon/www.nike.com'],
                    ['name' => 'Adidas', 'logo' => 'https://icon.horse/icon/www.adidas.com'],
                    ['name' => 'Puma', 'logo' => 'https://icon.horse/icon/www.puma.com']
                ],
                'Home & Kitchen' => [
                    ['name' => 'IKEA', 'logo' => 'https://icon.horse/icon/www.ikea.com'],
                    ['name' => 'Tefal', 'logo' => 'https://icon.horse/icon/www.tefal.com'],
                    ['name' => 'Philips', 'logo' => 'https://icon.horse/icon/www.philips.com']
                ],
                'Beauty & Personal Care' => [
                    ['name' => 'L\'Oréal', 'logo' => 'https://icon.horse/icon/www.loreal.com'],
                    ['name' => 'Dove', 'logo' => 'https://icon.horse/icon/www.dove.com'],
                    ['name' => 'Nivea', 'logo' => 'https://icon.horse/icon/www.nivea.com']
                ],
                'Accessories' => [
                    ['name' => 'Rolex', 'logo' => 'https://icon.horse/icon/www.rolex.com'],
                    ['name' => 'Cartier', 'logo' => 'https://icon.horse/icon/www.cartier.com'],
                    ['name' => 'Omega', 'logo' => 'https://icon.horse/icon/www.omega.com']
                ],
                'Shoes' => [
                    ['name' => 'Adidas', 'logo' => 'https://icon.horse/icon/www.adidas.com'],
                    ['name' => 'Nike', 'logo' => 'https://icon.horse/icon/www.nike.com'],
                    ['name' => 'Puma', 'logo' => 'https://icon.horse/icon/www.puma.com']
                ],
                'Bags' => [
                    ['name' => 'Louis Vuitton', 'logo' => 'https://icon.horse/icon/www.louisvuitton.com'],
                    ['name' => 'Chanel', 'logo' => 'https://icon.horse/icon/www.chanel.com'],
                    ['name' => 'Gucci', 'logo' => 'https://icon.horse/icon/www.gucci.com']
                ],
                'Jewelry' => [
                    ['name' => 'Tiffany & Co.', 'logo' => 'https://icon.horse/icon/www.tiffany.com'],
                    ['name' => 'Cartier', 'logo' => 'https://icon.horse/icon/www.cartier.com'],
                    ['name' => 'Omega', 'logo' => 'https://icon.horse/icon/www.omega.com']
                ],
                'Toys & Games' => [
                    ['name' => 'Lego', 'logo' => 'https://icon.horse/icon/www.lego.com'],
                    ['name' => 'Hasbro', 'logo' => 'https://icon.horse/icon/www.hasbro.com'],
                    ['name' => 'Mattel', 'logo' => 'https://icon.horse/icon/www.mattel.com']
                ],
                'Art & Collectibles' => [
                    ['name' => 'Van Gogh', 'logo' => 'https://icon.horse/icon/www.vangoghgallery.com'],
                    ['name' => 'Picasso', 'logo' => 'https://icon.horse/icon/www.picasso.com'],
                    ['name' => 'Monet', 'logo' => 'https://icon.horse/icon/www.claude-monet.com']
                ],
                'Automotive' => [
                    ['name' => 'Mercedes-Benz', 'logo' => 'https://icon.horse/icon/www.mercedes-benz.com'],
                    ['name' => 'BMW', 'logo' => 'https://icon.horse/icon/www.bmw.com'],
                    ['name' => 'Audi', 'logo' => 'https://icon.horse/icon/www.audi.com']
                ]
            ]

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

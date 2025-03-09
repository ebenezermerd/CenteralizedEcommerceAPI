<?php

namespace App\Traits;

trait ProductFieldMapper
{
    protected static array $fieldMappings = [
        'coverUrl' => 'cover_img',
        'priceSale' => 'price_sale',
        'subDescription' => 'caption',
        'available' => 'inStock',
        'totalSold' => 'sold',
        'totalRatings' => 'rating',
        'category' => 'category_id',
        'inventoryType' => 'inventory_type',
        'newLabel' => 'is_new',
        'saleLabel' => 'sale_label'
    ];

    protected function mapToDatabase(array $data): array
    {
        $mapped = [];
        foreach ($data as $key => $value) {
            $dbField = self::$fieldMappings[$key] ?? $key;
            $mapped[$dbField] = $value;
        }
        return $mapped;
    }

    protected function mapFromDatabase(array $data): array
    {
        $mapped = [];
        foreach ($data as $key => $value) {
            $frontendField = array_search($key, self::$fieldMappings) ?? $key;
            $mapped[$frontendField] = $value;
        }
        return $mapped;
    }
}

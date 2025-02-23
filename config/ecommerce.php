<?php

return [
    'shares' => [
        'company' => env('COMPANY_SHARE', 0.10),
        'vendor' => env('VENDOR_SHARE', 0.90),
    ],
    'analytics' => [
        'chart_periods' => [
            'categories' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ],
        'gender_categories' => ['Women', 'Men', 'Kids']
    ]
]; 
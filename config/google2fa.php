<?php

return [
    'enabled' => true,

    'verify' => [
        'window' => 1,
    ],

    'qr_code' => [
        'size' => 200,
        'margin' => 0,
        'error_correction' => 'L',
        'writer' => 'svg',
        'writer_options' => [],
        'image' => [
            'background_color' => '#FFFFFF',
            'foreground_color' => '#000000',
        ],
    ],

    'otp_secret_length' => 16,

    'key' => env('GOOGLE2FA_KEY', 'base32secret3232'),

    'issuer' => env('GOOGLE2FA_ISSUER', config('app.name')),

    'service' => PragmaRX\Google2FAQRCode\Google2FA::class,
];

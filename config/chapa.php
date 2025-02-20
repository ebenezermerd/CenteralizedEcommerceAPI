<?php

/*
 * This file is part of the Laravel Rave package.
 *
 * Kidus Yared - @kidus363 <kidusy@chapa.co>
 *
 *
 */
return [

    /**
     * Public Key: Your Chapa publicKey. Sign up on https://dashboard.chapa.co/ to get one from your settings page
     *
     */
    'publicKey' => env('CHAPA_PUBLIC_KEY'),

    /**
     * Secret Key: Your chapa secretKey. Sign up on https://dashboard.chapa.co/ to get one from your settings page
     *
     */
    'secretKey' => env('CHAPA_SECRET_KEY'),

    /**
     * Secret for webhook
     *
     */
    'webhookSecret' => env('CHAPA_WEBHOOK_SECRET'),

    // Default URLs if not provided in the request
    'success_url' => env('CHAPA_SUCCESS_URL', '/payment/success'),
    'cancel_url' => env('CHAPA_CANCEL_URL', '/payment/failed'),
];

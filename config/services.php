<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency'       => env('STRIPE_CURRENCY', 'usd'),
        'bob_usd_rate'   => (float) env('STRIPE_BOB_USD_RATE', 6.96),
    ],

    'pagofacil' => [
        'url'               => env('PAGOFACIL_URL', 'https://masterqr.pagofacil.com.bo/api/services/v2'),
        'commerce_id'       => env('PAGOFACIL_COMMERCE_ID'),
        'token_service'     => env('PAGOFACIL_TOKEN_SERVICE'),
        'token_secret'      => env('PAGOFACIL_TOKEN_SECRET'),
        'payment_method_id' => (int) env('PAGOFACIL_PAYMENT_METHOD_ID', 34),
        'currency'          => (int) env('PAGOFACIL_CURRENCY', 2),
    ],

];

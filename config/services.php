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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'oracle_mbc' => [
        'host' => env('MBC_DB_HOST', 'localhost'),
        'port' => env('MBC_DB_PORT', 1521),
        'database' => env('MBC_DB_DATABASE', 'mbc_db'),
        'username' => env('MBC_DB_USERNAME', 'mbc_user'),
        'password' => env('MBC_DB_PASSWORD', 'mbc_pass'),
    ],

    'jpos' => [
        'host' => env('JPOS_ENGINE_HOST', 'localhost'),
        'port' => env('JPOS_ENGINE_PORT', 1521),
    ],

];

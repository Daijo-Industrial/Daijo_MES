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

    'customer_api' => [
        'token' => env('CUSTOMER_API_TOKEN'),
    ],

    'sap' => [
        'base_url' => env('SAP_BASE_URL', 'http://192.168.6.149:9001'),
        'auth_url' => env('SAP_AUTH_URL', 'http://192.168.6.149:9001/auth/token'),
        'company_db' => env('SAP_COMPANY_DB'),
        'username' => env('SAP_USERNAME'),
        'password' => env('SAP_PASSWORD'),
        'timeout_alert_recipients' => array_values(array_filter(array_map('trim', explode(',', env('SAP_TIMEOUT_ALERT_EMAILS', 'andreas@daijo.co.id,timo@daijo.co.id,thomasilver88@gmail.com'))))),
    ],

    'api_secret_key' => env('API_SECRET_KEY'),

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_ALERT_CHAT_ID'),
        'topic_id' => env('TELEGRAM_ALERT_TOPIC_ID'),
    ],

];

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

    'mysgoc' => [
        'url' => env('MYSGOC_BASE_URL'),
        'api_key' => env('MYSGOC_API_KEY'),
    ],

    'iapp' => [
        'url' => env('IAPP_BASE_URL', 'https://api.iapp.co.th'),
        'api_key' => env('IAPP_API_KEY'),
    ],

    'mmbcnerp' => [
        'url' => env('MMBCNERP_API_URL'),
        'api_key' => env('MMBCNERP_TOKEN'),
    ],

    'rm' => [
        'url' => env('RM_API_URL'),
        'api_key' => env('RM_TOKEN'),
    ],

    'partition_slack' => [
        'webhook_url' => env('PARTITION_SLACK_WEBHOOK_URL'),
    ],

    'slack' => [
        'vms_etl_webhook' => env('SLACK_VMS_ETL_WEBHOOK_URL'),
        'vms_etl_channel' => env('SLACK_VMS_ETL_CHANNEL', '#vms-alerts'),
    ],
];

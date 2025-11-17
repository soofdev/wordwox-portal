<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default SMS provider that will be used to send
    | SMS messages. You may set this to any of the providers defined in the
    | "providers" array below.
    |
    */

    'default' => env('SMS_DEFAULT_PROVIDER', 'blunet'),

    /*
    |--------------------------------------------------------------------------
    | SMS Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure the SMS providers for your application. Each
    | provider may have multiple configurations, allowing you to have multiple
    | SMS providers using the same driver.
    |
    */

    'providers' => [
        'blunet' => [
            'driver' => 'blunet',
            'endpoint' => env('SMS_BLUNET_ENDPOINT', 'http://82.212.81.40:8080/websmpp/websms'),
            'access_key' => env('SMS_BLUNET_ACCESS_KEY', '*****'),
            'type' => env('SMS_BLUNET_TYPE', 4),
            'default_sender' => env('SMS_BLUNET_DEFAULT_SENDER', 'Wodworx'),
            
            // Org-specific sender names (matches Yii2 config)
            'sender_map' => [
                'default' => 'Wodworx',
                '1' => 'QuickSand',
                '23' => 'DNA Fitness',
                '24' => 'AllOut',
                '28' => 'KABS',
                '30' => 'OneWitNatur',
                '35' => '962Athltics',
                '43' => 'JoTutor',
                '101' => 'ArmyGym',
                '104' => 'ArmyGym',
                '103' => 'MagmaGym',
            ],
        ],

        // 'twilio' => [
        //     'driver' => 'twilio',
        //     'sid' => env('SMS_TWILIO_SID'),
        //     'token' => env('SMS_TWILIO_TOKEN'),
        //     'from' => env('SMS_TWILIO_FROM'),
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS service behavior, routing, and validation.
    |
    */

    'service' => [
        // Global SMS enable/disable toggle
        'enabled' => env('SMS_ENABLED', false),

        // Provider routing by country code (matches Yii2 smsActiveService config)
        'country_routing' => [
            'default' => 'blunet',
            'exceptions' => [
                // '1' => 'twilio',      // US/Canada (disabled until Twilio implemented)
                // '36' => 'twilio',     // Hungary (disabled until Twilio implemented)
                // '971' => 'twilio',    // UAE (disabled until Twilio implemented)
                // '966' => 'twilio',    // Saudi Arabia (disabled until Twilio implemented)
            ],
        ],

        // Country blacklist (matches Yii2 config)
        'blacklisted_countries' => ['92', '88', '977', '43', '964'],

        // Phone number validation
        'validate_before_send' => env('SMS_VALIDATE_BEFORE_SEND', true),

        // Skip SMS in development environment
        'skip_in_dev' => env('SMS_SKIP_IN_DEV', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | SMS messages will be queued for processing. You can configure the
    | queue connection and job settings here.
    |
    */

    'queue' => [
        'connection' => env('SMS_QUEUE_CONNECTION', 'database'),
        'queue' => env('SMS_QUEUE_NAME', 'sms'),
        'timeout' => 30,
        'retry_attempts' => 1,
    ],
];
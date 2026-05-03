<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AlertStream Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains additional logging channel configurations for AlertStream.
    | Publish this file to your config/logging.php for custom channel setup.
    |
    */

    'channels' => [
        'alertstream' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'alertstream_email' => [
            'driver' => 'stack',
            'channels' => ['single', 'mail'],
            'ignore_exceptions' => false,
        ],

        'alertstream_slack' => [
            'driver' => 'stack',
            'channels' => ['single', 'slack'],
            'ignore_exceptions' => false,
        ],

        'alertstream_syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
        ],
    ],
];

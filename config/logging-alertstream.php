<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AlertStream Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Custom Monolog channel drivers for routing Laravel log messages to
    | AlertStream destinations.  Copy the entries you need into your
    | config/logging.php channels array, then reference them by name in
    | your logging stack or ALERTSTREAM_LOG_CHANNELS.
    |
    | Each driver reads its webhook/address from alertstream.log_destinations,
    | which is keyed by ALERTSTREAM_LOG_* env vars — separate from the alert
    | channel credentials so you can route logs to a different endpoint.
    |
    */

    'channels' => [

        'alertstream_slack' => [
            'driver' => 'custom',
            'via' => NightshiftFoundry\AlertStream\LogChannels\SlackLogChannel::class,
            'level' => 'debug',
        ],

        'alertstream_teams' => [
            'driver' => 'custom',
            'via' => NightshiftFoundry\AlertStream\LogChannels\TeamsLogChannel::class,
            'level' => 'debug',
        ],

        'alertstream_discord' => [
            'driver' => 'custom',
            'via' => NightshiftFoundry\AlertStream\LogChannels\DiscordLogChannel::class,
            'level' => 'debug',
        ],

        'alertstream_mail' => [
            'driver' => 'custom',
            'via' => NightshiftFoundry\AlertStream\LogChannels\MailLogChannel::class,
            'level' => 'debug',
        ],

    ],
];

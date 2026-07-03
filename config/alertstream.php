<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AlertStream Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the AlertStream package for logging and reporting debug
    | information with full stacktraces to multiple channels.
    |
    */

    'enabled' => env('ALERTSTREAM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log AlertChannels
    |--------------------------------------------------------------------------
    |
    | ALERTSTREAM_LOG_CHANNELS is a comma-separated list of channel NAMES that
    | log() delivers to — mirroring the vocabulary of channels.active below.
    |
    |   ALERTSTREAM_LOG_CHANNELS=slack,teams,discord,mail
    |
    | Each name maps to the auto-registered `alertstream_<name>` Monolog driver
    | and reads its destination from log_destinations.* (ALERTSTREAM_LOG_* env
    | vars) ONLY — there is no fallback to the matching channels.* webhook, so
    | a channel with no log destination configured is silently skipped.
    |
    | Logs are ALWAYS also written to the auto-registered `alertstream_log`
    | file channel (storage/logs/alertstream-log.log), so leaving this empty
    | still keeps a local record. This file is exclusive to log() — report()
    | writes exceptions to a separate `alertstream` file channel — so an
    | exception can never show up in a file (or webhook) that log() writes to.
    |
    */

    'log_channels' => array_filter(explode(',', env('ALERTSTREAM_LOG_CHANNELS', ''))),

    /*
    |--------------------------------------------------------------------------
    | Alert Level
    |--------------------------------------------------------------------------
    |
    | The default logging level for alerts.
    | Options: 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
    |
    */

    'level' => env('ALERTSTREAM_LEVEL', 'alert'),

    /*
    |--------------------------------------------------------------------------
    | Report Exceptions
    |--------------------------------------------------------------------------
    |
    | Automatically report exceptions caught by the AlertStream handler.
    |
    */

    'report_exceptions' => env('ALERTSTREAM_REPORT_EXCEPTIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Muted Exceptions
    |--------------------------------------------------------------------------
    |
    | Exception classes listed here are silently ignored — they will never be
    | sent to any channel or written to the AlertStream log.
    |
    | The defaults cover common non-actionable Laravel exceptions. Add any
    | exception class you want to suppress, using the fully-qualified name.
    |
    */

    'mute' => [
        Illuminate\Auth\AuthenticationException::class,
        Illuminate\Auth\AuthorizationException::class,
        Illuminate\Validation\ValidationException::class,
        Illuminate\Http\Exceptions\HttpResponseException::class,
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Severity Map
    |--------------------------------------------------------------------------
    |
    | Override the auto-detected severity for specific exception classes.
    | Values: 'critical', 'error', 'warning'.
    |
    */

    'severity_map' => [
        // \App\Exceptions\PaymentFailedException::class => 'critical',
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Enrichers
    |--------------------------------------------------------------------------
    |
    | Invokable classes that add custom data to every alert context.
    | Each must implement: __invoke(array $context, Throwable $e): array
    |
    */

    'context_enrichers' => [],

    /*
    |--------------------------------------------------------------------------
    | Include Stacktrace
    |--------------------------------------------------------------------------
    |
    | Include full stacktrace in log output.
    |
    */

    'include_stacktrace' => env('ALERTSTREAM_INCLUDE_STACKTRACE', true),

    /*
    |--------------------------------------------------------------------------
    | Additional Context
    |--------------------------------------------------------------------------
    |
    | Additional context data to include with every alert.
    |
    */

    'context' => [
        'include_hostname' => env('ALERTSTREAM_INCLUDE_HOSTNAME', true),
        'include_environment' => env('ALERTSTREAM_INCLUDE_ENVIRONMENT', true),
        'include_request_id' => env('ALERTSTREAM_INCLUDE_REQUEST_ID', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Throttling
    |--------------------------------------------------------------------------
    |
    | Suppress repeat alerts for the same exception (same class + file +
    | line). The first occurrence of a fingerprint opens a window lasting
    | ALERTSTREAM_THROTTLE_COOLDOWN_MINUTES and is always allowed; up to
    | ALERTSTREAM_THROTTLE_MAX occurrences total are allowed inside that
    | same window, then every further occurrence is dropped until the
    | window elapses — however far apart the occurrences are. The window
    | is fixed (it starts on the first hit and is never extended by later
    | hits), so a bug recurring every few minutes forever is still capped
    | at ALERTSTREAM_THROTTLE_MAX alerts per ALERTSTREAM_THROTTLE_COOLDOWN_MINUTES,
    | not delivered on every single occurrence.
    |
    */

    'throttle' => [
        'enabled' => env('ALERTSTREAM_THROTTLE', true),
        'max' => env('ALERTSTREAM_THROTTLE_MAX', 5),
        'cooldown_minutes' => env('ALERTSTREAM_THROTTLE_COOLDOWN_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | When ALERTSTREAM_QUEUE=true (default), exception reporting is dispatched
    | as a queued event and processed by a worker in the background, keeping it
    | completely off the request hot path.
    |
    | Set ALERTSTREAM_QUEUE=false to skip the queue entirely and report
    | synchronously on the same process — useful when you don't run a dedicated
    | worker or just want the simplest possible setup.
    |
    | Use ALERTSTREAM_QUEUE_CONNECTION to target a specific queue backend
    | (e.g. "redis", "sqs") or leave null to use the app default.
    | Use ALERTSTREAM_QUEUE_NAME to route reports to a dedicated queue
    | (e.g. "alertstream") so they don't compete with business jobs.
    |
    */

    'queue' => env('ALERTSTREAM_QUEUE', true),

    'queue_connection' => env('ALERTSTREAM_QUEUE_CONNECTION'),

    'queue_name' => env('ALERTSTREAM_QUEUE_NAME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | AlertChannels
    |--------------------------------------------------------------------------
    |
    | ALERTSTREAM_CHANNELS is a comma-separated list of the alerting channels
    | you want active.  Only listed channels are booted — everything else is
    | completely ignored at zero cost.
    |
    |   ALERTSTREAM_CHANNELS=slack,discord
    |
    | Available built-in values: slack, teams, discord, mail
    |
    | Need a channel that isn't listed here?  Implement the AlertChannel
    | contract and tag your class with 'alertstream.channel' in any service
    | provider — it is discovered automatically alongside the built-in ones.
    |
    |   $this->app->bind(MyChannel::class);
    |   $this->app->tag([MyChannel::class], 'alertstream.channel');
    |
    */

    'channels' => [

        'active' => array_filter(explode(',', env('ALERTSTREAM_CHANNELS', ''))),

        'slack' => [
            'webhook' => env('ALERTSTREAM_SLACK_WEBHOOK'),
        ],

        'teams' => [
            'webhook' => env('ALERTSTREAM_TEAMS_WEBHOOK'),
        ],

        'discord' => [
            'webhook' => env('ALERTSTREAM_DISCORD_WEBHOOK'),
        ],

        'mail' => [
            'to' => env('ALERTSTREAM_MAIL_TO'),
            'from' => env('ALERTSTREAM_MAIL_FROM'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channel Destinations
    |--------------------------------------------------------------------------
    |
    | Webhook/mail destinations for the AlertStream Monolog log channel drivers.
    | These are intentionally separate from the alert channel credentials above
    | so you can route log messages to a different endpoint than exceptions.
    |
    | There is no fallback to channels.* above — a channel left unset here is
    | simply not delivered to, ensuring report() and log() never converge on
    | the same destination.
    |
    */

    'log_destinations' => [

        'slack' => [
            'webhook' => env('ALERTSTREAM_LOG_SLACK_WEBHOOK'),
        ],

        'teams' => [
            'webhook' => env('ALERTSTREAM_LOG_TEAMS_WEBHOOK'),
        ],

        'discord' => [
            'webhook' => env('ALERTSTREAM_LOG_DISCORD_WEBHOOK'),
        ],

        'mail' => [
            'to' => env('ALERTSTREAM_LOG_MAIL_TO'),
            'from' => env('ALERTSTREAM_LOG_MAIL_FROM'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Snapshots
    |--------------------------------------------------------------------------
    |
    | When enabled, each exception is persisted to the database so the full
    | stacktrace can be viewed later via a secure, hash-based URL.  A link
    | to the snapshot is included in every channel message.
    |
    | Set ALERTSTREAM_SNAPSHOTS=false to disable.  When disabled, no
    | migrations are loaded, nothing is written to the database, and no
    | snapshot routes are registered.
    |
    */

    'snapshots' => [

        'enabled' => env('ALERTSTREAM_SNAPSHOTS', false),

        // Database table name for storing snapshots
        'table' => env('ALERTSTREAM_SNAPSHOTS_TABLE', 'alertstream_snapshots'),

        // Snapshots older than this are eligible for pruning via
        // `php artisan alertstream:prune-snapshots`
        'retention_days' => env('ALERTSTREAM_SNAPSHOTS_RETENTION', 30),

        // Dedup window in minutes — same exception (class+file+line) updates
        // an existing snapshot instead of creating a new record. Set to 0 to disable.
        'dedup_minutes' => env('ALERTSTREAM_SNAPSHOTS_DEDUP_MINUTES', 60),

        // Route prefix and middleware for the snapshot viewer
        'route_prefix' => env('ALERTSTREAM_SNAPSHOTS_ROUTE_PREFIX', 'alertstream'),
        // Route middleware adds 'auth' for login-protected access or any other middleware
        'route_middleware' => ['web', 'auth'],
    ],
];

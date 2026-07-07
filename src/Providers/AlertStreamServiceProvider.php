<?php

namespace NightshiftFoundry\AlertStream\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use NightshiftFoundry\AlertStream\AlertChannels\DiscordChannel;
use NightshiftFoundry\AlertStream\AlertChannels\MailChannel;
use NightshiftFoundry\AlertStream\AlertChannels\SlackChannel;
use NightshiftFoundry\AlertStream\AlertChannels\TeamsChannel;
use NightshiftFoundry\AlertStream\Commands\PruneSnapshotsCommand;
use NightshiftFoundry\AlertStream\Commands\TestAlertCommand;
use NightshiftFoundry\AlertStream\Events\ExceptionCaptured;
use NightshiftFoundry\AlertStream\Exceptions\Handler;
use NightshiftFoundry\AlertStream\Listeners\SendExceptionToAlertStream;
use NightshiftFoundry\AlertStream\LogChannels\DiscordLogChannel;
use NightshiftFoundry\AlertStream\LogChannels\MailLogChannel;
use NightshiftFoundry\AlertStream\LogChannels\SlackLogChannel;
use NightshiftFoundry\AlertStream\LogChannels\TeamsLogChannel;
use NightshiftFoundry\AlertStream\Services\AlertStreamService;
use NightshiftFoundry\AlertStream\Services\SnapshotService;
use Throwable;

class AlertStreamServiceProvider extends ServiceProvider
{
    /**
     * Built-in channels and their concrete classes.
     *
     * @var array<string, class-string>
     */
    protected array $builtInChannels = [
        'slack' => SlackChannel::class,
        'teams' => TeamsChannel::class,
        'discord' => DiscordChannel::class,
        'mail' => MailChannel::class,
    ];

    /**
     * Register services.
     *
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/alertstream.php',
            'alertstream'
        );

        $this->registerLogChannels();

        $this->app->singleton(AlertStreamService::class, function ($app) {
            return new AlertStreamService(
                $app['config']['alertstream'],
                $app['log'],
                $app->make(Container::class)
            );
        });

        $this->app->alias(AlertStreamService::class, 'alertstream');

        // Register the exception handler
        $this->app->singleton(Handler::class, function ($app) {
            return new Handler(
                $app->make(AlertStreamService::class)
            );
        });

        // Snapshot service — always available, gates itself internally
        $this->app->singleton(SnapshotService::class);

        // Register built-in channels that are active in config
        $this->registerBuiltInChannels();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/alertstream.php' => config_path('alertstream.php'),
        ], 'alertstream-config');
        $this->publishes([
            __DIR__ . '/../../config/logging-alertstream.php' => config_path('logging-alertstream.php'),
        ], 'alertstream-logging');

        // Register the queued listener that processes ExceptionCaptured events
        Event::listen(ExceptionCaptured::class, SendExceptionToAlertStream::class);

        // Automatically register exception handler callback
        $this->registerExceptionHandler();

        // Flush the runtime context bag at the end of each request/job so the
        // AlertStreamService singleton never leaks context across requests
        // or jobs in long-lived runtimes (Octane, queue workers).
        $this->registerContextFlushing();

        // Always register views so the email template is available regardless of
        // whether snapshots are enabled.
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'alertstream');

        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/alertstream'),
        ], 'alertstream-views');

        // Conditionally boot snapshot infrastructure
        if ($this->app['config']['alertstream']['snapshots']['enabled'] ?? false) {
            $this->bootSnapshots();
        }

        if ($this->app->runningInConsole()) {
            $commands = [TestAlertCommand::class];

            if ($this->app['config']['alertstream']['snapshots']['enabled'] ?? false) {
                $commands[] = PruneSnapshotsCommand::class;
            }

            $this->commands($commands);
        }
    }

    /**
     * Register the AlertStream logging channels.
     *
     * Registration is conditional: a channel is only defined when the host
     * application has not already declared it, so every channel stays fully
     * overridable from the app's own config/logging.php.
     *
     * - alertstream: the always-on daily file channel written to exclusively
     *   by report() (storage/logs/alertstream.log).
     * - alertstream_log: the always-on daily file channel written to
     *   exclusively by log() (storage/logs/alertstream-log.log). Kept
     *   separate from `alertstream` so an exception reported via report()
     *   can never show up in a file that log() also writes to.
     * - alertstream_<name>: custom Monolog drivers used by log() when the
     *   matching short name is listed in ALERTSTREAM_LOG_CHANNELS.
     */
    protected function registerLogChannels(): void
    {
        $config = $this->app->make('config');

        $channels = [
            'logging.channels.alertstream' => [
                'driver' => 'daily',
                'path' => storage_path('logs/alertstream.log'),
                'level' => 'debug',
                'days' => 14,
            ],
            'logging.channels.alertstream_log' => [
                'driver' => 'daily',
                'path' => storage_path('logs/alertstream-log.log'),
                'level' => 'debug',
                'days' => 14,
            ],
            'logging.channels.alertstream_slack' => [
                'driver' => 'custom',
                'via' => SlackLogChannel::class,
                'level' => 'debug',
            ],
            'logging.channels.alertstream_teams' => [
                'driver' => 'custom',
                'via' => TeamsLogChannel::class,
                'level' => 'debug',
            ],
            'logging.channels.alertstream_discord' => [
                'driver' => 'custom',
                'via' => DiscordLogChannel::class,
                'level' => 'debug',
            ],
            'logging.channels.alertstream_mail' => [
                'driver' => 'custom',
                'via' => MailLogChannel::class,
                'level' => 'debug',
            ],
        ];

        foreach ($channels as $key => $definition) {
            if (! $config->has($key)) {
                $config->set($key, $definition);
            }
        }
    }

    /**
     * Boot snapshot-specific infrastructure: migrations, views, routes.
     *
     * Only called when ALERTSTREAM_SNAPSHOTS=true.
     */
    protected function bootSnapshots(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/alertstream.php');
    }

    /**
     * Bind and tag each active built-in channel so the service can discover
     * them automatically via $container->tagged('alertstream.channel').
     */
    protected function registerBuiltInChannels(): void
    {
        // Read the full alertstream config (not just channels.*) so the
        // global extra_link block below can be merged into every channel.
        $alertstreamConfig = $this->app['config']['alertstream'] ?? [];
        $config = $alertstreamConfig['channels'] ?? [];
        $active = $config['active'] ?? [];
        $toTag = [];

        foreach ($this->builtInChannels as $name => $class) {
            if (! in_array($name, $active, true)) {
                continue;
            }

            $channelConfig = $config[$name] ?? [];

            // Merge the single global extra_link into each built-in channel's
            // own config slice so it can render it alongside the snapshot
            // link. Custom third-party channels are not touched here — they
            // read config (including alertstream.extra_link) themselves.
            $channelConfig['extra_link'] = $alertstreamConfig['extra_link'] ?? [];

            $this->app->bind($class, function ($app) use ($class, $channelConfig) {
                return match ($class) {
                    MailChannel::class => new $class($channelConfig, $app->make(Mailer::class)),
                    default => new $class($channelConfig, $app->make(HttpClient::class)),
                };
            });

            $toTag[] = $class;
        }

        if (! empty($toTag)) {
            $this->app->tag($toTag, 'alertstream.channel');
        }
    }

    /**
     * Register the automatic exception handler.
     *
     * This hooks into Laravel's exception reporting without requiring users
     * to manually register anything in their Handler.php
     */
    protected function registerExceptionHandler(): void
    {
        // Get the application's exception handler
        try {
            $exceptionHandler = $this->app->make('Illuminate\Contracts\Debug\ExceptionHandler');
            // Use Laravel's reportable() method to register our handler
            if (method_exists($exceptionHandler, 'reportable')) {
                $exceptionHandler->reportable(function (Throwable $e): void {
                    $handler = $this->app->make(Handler::class);
                    $handler->handle($e);
                });
            }
        } catch (Throwable $e) {
            // Silently fail if exception handler is not available
            // This prevents issues during application bootstrap
        }
    }

    /**
     * Register automatic flushing of the runtime context bag.
     *
     * AlertStreamService is a singleton, so its runtime context bag (pushed
     * to via AlertStream::addContext()) would otherwise bleed from one
     * request/job into the next in long-lived runtimes such as Laravel
     * Octane or a queue worker process. Two hooks keep it scoped to a single
     * request/console lifecycle or a single queue job:
     *
     * - app()->terminating(): fires at the end of every request/console
     *   lifecycle, and per-request under Octane.
     * - Queue::after(): fires after every processed queue job.
     *
     * Both guard with $this->app->resolved() first, so flushing never forces
     * the service to be instantiated when nothing ever used it.
     */
    protected function registerContextFlushing(): void
    {
        $this->app->terminating(function (): void {
            if ($this->app->resolved(AlertStreamService::class)) {
                $this->app->make(AlertStreamService::class)->flushContext();
            }
        });

        Queue::after(function (): void {
            if ($this->app->resolved(AlertStreamService::class)) {
                $this->app->make(AlertStreamService::class)->flushContext();
            }
        });
    }
}

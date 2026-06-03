<?php

namespace NightshiftFoundry\AlertStream\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Log\LogManager;
use NightshiftFoundry\AlertStream\AlertChannels\Contracts\AlertChannel;
use NightshiftFoundry\AlertStream\Exceptions\AlertStreamException;
use Throwable;

class AlertStreamService
{
    /**
     * Configuration array
     *
     * @var array
     */
    protected $config;

    /**
     * Log manager instance
     *
     * @var LogManager
     */
    protected $log;

    /**
     * IoC container — used to lazily resolve tagged AlertChannel instances.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Create a new service instance.
     *
     * @param array $config
     * @param LogManager $log
     * @param Container $container
     */
    public function __construct(array $config, LogManager $log, Container $container)
    {
        $this->config = $config;
        $this->log = $log;
        $this->container = $container;
    }

    /**
     * Report an alert with full stacktrace.
     *
     * This is the primary method for exception-based alerts. It writes to
     * your configured log channels AND dispatches notifications to all
     * active alert channels (Slack, Teams, Discord, Mail, etc.).
     * Snapshots, throttling, and deduplication all apply.
     *
     * @param string $message
     * @param Throwable|null $exception
     * @param array $context
     *
     * @throws AlertStreamException
     */
    public function report(string $message, ?Throwable $exception = null, array $context = []): void
    {
        if (! $this->config['enabled']) {
            throw new AlertStreamException('AlertStream is not enabled. Check your ALERTSTREAM_ENABLED environment variable.');
        }

        $logChannels = $this->config['log_channels'] ?? ['single'];

        $logData = array_merge([
            'timestamp' => now(),
            'host' => gethostname(),
        ], $context);

        if ($exception) {
            $logData['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        foreach ($logChannels as $logChannel) {
            $this->log->channel($logChannel)->alert($message, $logData);
        }

        // Dispatch to every tagged AlertChannel (Slack, Teams, Discord, Mail, …).
        // Custom channels registered by the host app are discovered here too.
        if ($exception !== null) {
            $this->dispatchToChannels($message, $exception, $context);
        }
    }

    /**
     * Log a structured message at the given level.
     *
     * Writes to your configured Laravel log channels ONLY — does NOT
     * dispatch to notification channels (Slack, Teams, etc.) and does
     * NOT create snapshots. Use report() when you need channel notifications.
     *
     * Accepts any log level supported by Laravel / PSR-3:
     * emergency, alert, critical, error, warning, notice, info, debug.
     *
     * @param string $level Any PSR-3 log level string
     * @param string $message
     * @param mixed $data
     * @param array $context
     *
     * @throws AlertStreamException
     */
    public function log(string $level, string $message, $data = null, array $context = []): void
    {
        if (! $this->config['enabled']) {
            throw new AlertStreamException('AlertStream is not enabled. Check your ALERTSTREAM_ENABLED environment variable.');
        }

        $logChannels = $this->config['log_channels'] ?? ['single'];

        $logData = array_merge([
            'timestamp' => now(),
            'data' => $data,
        ], $context);

        foreach ($logChannels as $logChannel) {
            $this->log->channel($logChannel)->$level($message, $logData);
        }
    }

    /**
     * Convenience alias for log('debug', ...).
     *
     * @param string $message
     * @param mixed $data
     * @param array $context
     *
     * @throws AlertStreamException
     */
    public function debug(string $message, $data = null, array $context = []): void
    {
        $this->log('debug', $message, $data, $context);
    }

    /**
     * Get configuration value.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Dispatch the alert to all registered AlertChannel implementations.
     *
     * Creates a snapshot (if enabled) and passes the URL to each channel.
     * Failures are swallowed per-channel so one broken channel never
     * prevents the others from running.
     *
     * @param string $title
     * @param Throwable $exception
     * @param array $context
     */
    protected function dispatchToChannels(string $title, Throwable $exception, array $context): void
    {
        try {
            // Create a snapshot so channels can link to the full stacktrace.
            // Returns null when snapshots are disabled — channels handle that.
            $snapshotUrl = $this->container->make(SnapshotService::class)
                ->capture($title, $exception, $context);

            /** @var AlertChannel $channel */
            foreach ($this->container->tagged('alertstream.channel') as $channel) {
                try {
                    $channel->send($title, $exception, $context, $snapshotUrl);
                } catch (Throwable) {
                    // One channel failing must not block the rest
                }
            }
        } catch (Throwable) {
            // No channels tagged or snapshot service unavailable — nothing to do
        }
    }
}

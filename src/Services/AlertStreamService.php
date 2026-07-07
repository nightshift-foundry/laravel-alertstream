<?php

namespace NightshiftFoundry\AlertStream\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Log\LogManager;
use NightshiftFoundry\AlertStream\AlertChannels\Contracts\AlertChannel;
use NightshiftFoundry\AlertStream\Enums\AlertStreamLogLevel;
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
     * Runtime context bag — key/value pairs pushed at runtime via addContext()
     * and merged into every subsequently reported exception's context.
     *
     * Populated and drained entirely within a single request/job lifecycle
     * (see AlertStreamServiceProvider::registerContextFlushing()), since this
     * service is registered as a singleton and would otherwise bleed context
     * across requests/jobs in long-lived runtimes (Octane, queue workers).
     *
     * @var array<string, mixed>
     */
    protected array $runtimeContext = [];

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
     * This is the primary method for exception-based alerts. It writes to the
     * always-on `alertstream` file channel AND dispatches notifications to all
     * active alert channels (Slack, Teams, Discord, Mail, etc.).
     * Snapshots, throttling, and deduplication all apply.
     *
     * Note: report() intentionally does NOT write to the log webhook channels
     * (ALERTSTREAM_LOG_CHANNELS) or to the `alertstream_log` file channel.
     * Those belong exclusively to log() so that an exception is never
     * delivered — via any channel, file or webhook — anywhere log() writes.
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

        // Exceptions are written to the dedicated alertstream file only — the
        // log webhook channels are reserved for log(). Notification delivery
        // happens through the tagged AlertChannels below.
        $this->log->channel('alertstream')->alert($message, $logData);

        // Dispatch to every tagged AlertChannel (Slack, Teams, Discord, Mail, …).
        // Custom channels registered by the host app are discovered here too.
        if ($exception !== null) {
            $this->dispatchToChannels($message, $exception, $context);
        }
    }

    /**
     * Log a structured message at the given level.
     *
     * Writes to the always-on `alertstream_log` file channel AND to the log
     * webhook channels selected via ALERTSTREAM_LOG_CHANNELS (Slack, Teams,
     * Discord, Mail). It does NOT create snapshots or go through throttling.
     * Use report() when you need exception notifications to the alert channels.
     *
     * Note: `alertstream_log` is a dedicated file channel, separate from the
     * `alertstream` file channel report() writes to — so an exception can
     * never end up in a file that log() also writes to, and vice versa.
     *
     * Accepts any log level supported by Laravel / PSR-3:
     * emergency, alert, critical, error, warning, notice, info, debug.
     * An AlertStreamLogLevel enum case may be passed in place of the string.
     *
     * @param string|AlertStreamLogLevel $level Any PSR-3 log level string, or an AlertStreamLogLevel case
     * @param string $message
     * @param mixed $data
     * @param array $context
     *
     * @throws AlertStreamException
     */
    public function log(string|AlertStreamLogLevel $level, string $message, $data = null, array $context = []): void
    {
        if (! $this->config['enabled']) {
            throw new AlertStreamException('AlertStream is not enabled. Check your ALERTSTREAM_ENABLED environment variable.');
        }

        $level = $level instanceof AlertStreamLogLevel ? $level->value : $level;

        $logData = array_merge([
            'timestamp' => now(),
            'data' => $data,
        ], $context);

        foreach ($this->resolveLogChannels() as $logChannel) {
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
        $this->log(AlertStreamLogLevel::DEBUG, $message, $data, $context);
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
     * Push key/value pairs onto the runtime context bag.
     *
     * Merged (via array_merge, so later keys overwrite earlier ones) into
     * every subsequently reported exception's context by
     * Handler::buildContext(), which reads the bag synchronously — this is
     * what makes it safe to use even when reporting is queued.
     *
     * Values may be plain scalars/arrays, or callables. A callable value is
     * resolved lazily at report time, receiving the exception being reported
     * as its single argument — see Handler::buildContext(). A plain string
     * is never treated as callable here (e.g. a value of 'date' is kept as
     * the literal string, not invoked as a function name).
     *
     * No-op when the 'runtime_context' config key is disabled, so the bag
     * silently stays empty and getRuntimeContext() reflects that.
     *
     * @param array<string, mixed> $context
     */
    public function addContext(array $context): void
    {
        if (! $this->getConfig('runtime_context', true)) {
            return;
        }

        $this->runtimeContext = array_merge($this->runtimeContext, $context);
    }

    /**
     * Get the current runtime context bag.
     *
     * @return array<string, mixed>
     */
    public function getRuntimeContext(): array
    {
        return $this->runtimeContext;
    }

    /**
     * Reset the runtime context bag to empty.
     *
     * Called automatically at the end of each request/console lifecycle and
     * after each processed queue job (see
     * AlertStreamServiceProvider::registerContextFlushing()) so a
     * long-lived singleton never leaks context between requests/jobs under
     * Octane or a queue worker.
     */
    public function flushContext(): void
    {
        $this->runtimeContext = [];
    }

    /**
     * Resolve the Laravel logging channels that log() writes to.
     *
     * Always includes the dedicated `alertstream_log` file channel (never the
     * `alertstream` channel report() writes to), plus one `alertstream_<name>`
     * webhook channel for every short name configured in ALERTSTREAM_LOG_CHANNELS
     * (slack, teams, discord, mail) — mirroring the vocabulary of the alert
     * channels. The result is de-duplicated.
     *
     * @return array<int, string>
     */
    protected function resolveLogChannels(): array
    {
        $channels = ['alertstream_log'];

        foreach ($this->config['log_channels'] ?? [] as $name) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            $channels[] = 'alertstream_' . $name;
        }

        return array_values(array_unique($channels));
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

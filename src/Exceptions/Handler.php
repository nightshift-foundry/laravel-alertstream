<?php

namespace NightshiftFoundry\AlertStream\Exceptions;

use NightshiftFoundry\AlertStream\Events\ExceptionCaptured;
use NightshiftFoundry\AlertStream\Services\AlertStreamService;
use NightshiftFoundry\AlertStream\Services\ThrottleService;
use PDOException;
use Throwable;

class Handler
{
    /**
     * AlertStream service instance.
     *
     * @var AlertStreamService
     */
    protected AlertStreamService $alertStream;

    /**
     * Merged list of exception classes that must never be reported.
     *
     * Populated at construction from the 'mute' config key so users can
     * suppress exceptions purely through configuration.
     *
     * @var array<class-string<Throwable>>
     */
    protected array $dontReport = [];

    /**
     * Create a new exception handler instance.
     *
     * @param AlertStreamService $alertStream
     */
    public function __construct(AlertStreamService $alertStream)
    {
        $this->alertStream = $alertStream;

        // Merge the config-defined mute list so users can suppress exceptions
        // without touching any code — just add the class to 'mute' in config.
        $this->dontReport = array_unique(
            array_merge($this->dontReport, $alertStream->getConfig('mute', []))
        );
    }

    /**
     * Handle the exception by firing an ExceptionCaptured event.
     *
     * The handler is intentionally kept lean — it only performs cheap, synchronous
     * gate-checks and context collection (both are pure in-memory operations), then
     * immediately fires an event and returns.  The heavy I/O work (writing to log
     * channels / external services) is handled by the queued
     * SendExceptionToAlertStream listener, completely off the hot path.
     *
     * @param Throwable $e
     */
    public function handle(Throwable $e): void
    {
        // Gate-check: reporting must be enabled
        if (! $this->alertStream->getConfig('report_exceptions')) {
            return;
        }

        // Gate-check: skip exception types on the dontReport list
        if ($this->shouldntReport($e)) {
            return;
        }

        // Gate-check: throttle duplicate exceptions
        try {
            if (! app(ThrottleService::class)->allow($e)) {
                return;
            }
        } catch (Throwable) {
            // Cache unavailable — allow through
        }

        try {
            $context = $this->buildContext($e);
            $title = 'Exception: ' . class_basename($e);

            if ($this->alertStream->getConfig('queue', true)) {
                event(new ExceptionCaptured(
                    exception: $e,
                    title: $title,
                    context: $context,
                ));
            } else {
                $this->alertStream->report($title, $e, $context);
            }
        } catch (Throwable) {
            // Silently fail — never let reporting crash the application.
        }
    }

    /**
     * Add an exception type to the dontReport list.
     *
     * @param class-string<Throwable> $exception
     */
    public function dontReport(string $exception): void
    {
        if (! in_array($exception, $this->dontReport)) {
            $this->dontReport[] = $exception;
        }
    }

    /**
     * Remove an exception type from the dontReport list.
     *
     * @param class-string<Throwable> $exception
     */
    public function report(string $exception): void
    {
        $this->dontReport = array_filter(
            $this->dontReport,
            fn ($type) => $type !== $exception
        );
    }

    /**
     * Get the list of exceptions that should not be reported.
     *
     * @return array
     */
    public function getDontReportList(): array
    {
        return $this->dontReport;
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param Throwable $e
     *
     * @return bool
     */
    protected function shouldntReport(Throwable $e): bool
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build exception context information.
     *
     * @param Throwable $e
     *
     * @return array
     */
    protected function buildContext(Throwable $e): array
    {
        $context = [
            'exception_class' => get_class($e),
            'exception_code' => $e->getCode(),
            'severity' => $this->getExceptionSeverity($e),
        ];

        // Add user context if authenticated
        if (function_exists('auth') && auth()->check()) {
            $context['user_id'] = auth()->id();
            $context['user_email'] = auth()->user()?->email ?? null;
        }

        // Add request context if in HTTP context
        if (function_exists('request') && ! app()->runningInConsole()) {
            try {
                $context['url'] = request()->fullUrl();
                $context['method'] = request()->method();
                $context['ip'] = request()->ip();
                $context['user_agent'] = request()->header('User-Agent');
            } catch (Throwable $e) {
                // Request context might not be available
            }
        }

        // Merge runtime context pushed via AlertStream::addContext(). Callable
        // values (except plain-string callables like 'date') are resolved lazily
        // with the exception, each isolated so a throwing callable can only drop
        // its own key — never block reporting.
        if ($this->alertStream->getConfig('runtime_context', true)) {
            foreach ($this->alertStream->getRuntimeContext() as $key => $value) {
                try {
                    $context[$key] = (! is_string($value) && is_callable($value)) ? $value($e) : $value;
                } catch (Throwable) {
                    // Runtime context failure must never block reporting
                }
            }
        }

        // Run user-registered context enrichers
        foreach ($this->alertStream->getConfig('context_enrichers', []) as $enricher) {
            try {
                $context = app($enricher)($context, $e);
            } catch (Throwable) {
                // Enricher failure must never block reporting
            }
        }

        return $context;
    }

    /**
     * Determine the severity level of the exception.
     *
     * @param Throwable $e
     *
     * @return string
     */
    protected function getExceptionSeverity(Throwable $e): string
    {
        // Config-driven severity map takes priority
        $severityMap = $this->alertStream->getConfig('severity_map', []);
        foreach ($severityMap as $class => $severity) {
            if ($e instanceof $class) {
                return $severity;
            }
        }

        if ($e instanceof PDOException) {
            return 'critical';
        } elseif ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
            return 'warning';
        }

        return 'error';
    }
}

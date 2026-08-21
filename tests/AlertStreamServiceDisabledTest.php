<?php

namespace NightshiftFoundry\AlertStream\Tests;

use NightshiftFoundry\AlertStream\Providers\AlertStreamServiceProvider;
use NightshiftFoundry\AlertStream\Services\AlertStreamService;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * report() and log() are observability calls bolted onto business logic —
 * they must never be able to interrupt the caller's control flow just
 * because ALERTSTREAM_ENABLED=false. Regression coverage for the bug where
 * both methods used to throw AlertStreamException when disabled, which could
 * mask the real exception a caller was in the middle of handling.
 */
class AlertStreamServiceDisabledTest extends TestCase
{
    public function test_report_is_a_noop_when_disabled(): void
    {
        $service = $this->app->make(AlertStreamService::class);

        $service->report('should not throw', new RuntimeException('boom'));

        $this->addToAssertionCount(1);
    }

    public function test_log_is_a_noop_when_disabled(): void
    {
        $service = $this->app->make(AlertStreamService::class);

        $service->log('info', 'should not throw');

        $this->addToAssertionCount(1);
    }

    public function test_debug_is_a_noop_when_disabled(): void
    {
        $service = $this->app->make(AlertStreamService::class);

        $service->debug('should not throw');

        $this->addToAssertionCount(1);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            AlertStreamServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('alertstream.enabled', false);
    }
}

<?php

namespace NightshiftFoundry\AlertStream\Tests;

use NightshiftFoundry\AlertStream\Providers\AlertStreamServiceProvider;
use NightshiftFoundry\AlertStream\Services\AlertStreamService;
use Orchestra\Testbench\TestCase;

/**
 * With alertstream.runtime_context disabled, addContext() must be a no-op.
 *
 * Kept as its own test class (rather than toggling the setting mid-test)
 * because AlertStreamService snapshots config['runtime_context'] only
 * implicitly through getConfig() reads on the live array captured at
 * construction — setting it in defineEnvironment() guarantees the value is
 * in place before the service singleton is ever resolved.
 */
class RuntimeContextDisabledTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(AlertStreamServiceProvider::class, true);
    }

    /**
     * When runtime_context is disabled, addContext() is a no-op and the bag
     * stays empty no matter what is pushed onto it.
     */
    public function test_add_context_is_a_no_op_when_disabled(): void
    {
        $service = $this->app->make(AlertStreamService::class);

        $service->addContext(['request_id' => 'abc-123']);

        $this->assertSame([], $service->getRuntimeContext());
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
        $app['config']->set('alertstream.enabled', true);
        $app['config']->set('alertstream.queue', false);
        $app['config']->set('alertstream.throttle.enabled', false);
        $app['config']->set('alertstream.snapshots.enabled', false);
        $app['config']->set('alertstream.runtime_context', false);

        $app['config']->set('alertstream.channels.active', ['teams']);
        $app['config']->set('alertstream.channels.teams.webhook', 'https://example.test/runtime-context-disabled');
    }
}

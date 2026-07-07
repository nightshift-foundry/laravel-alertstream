<?php

namespace NightshiftFoundry\AlertStream\Tests;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessed;
use Mockery;
use NightshiftFoundry\AlertStream\Providers\AlertStreamServiceProvider;
use NightshiftFoundry\AlertStream\Services\AlertStreamService;
use Orchestra\Testbench\TestCase;

/**
 * Verifies the runtime context bag itself: AlertStream::addContext() merges
 * key/value data (later keys overwrite earlier ones), flushContext() resets
 * it, and the two automatic isolation hooks registered by
 * AlertStreamServiceProvider::registerContextFlushing() (app termination and
 * queue job completion) both empty the bag so the AlertStreamService
 * singleton never leaks context across requests/jobs.
 *
 * End-to-end reporting behaviour (context reaching a dispatched event,
 * closures resolved lazily, disabling the bag) lives in
 * RuntimeContextQueuedTest and RuntimeContextDisabledTest — both need a
 * different `alertstream.queue` / `alertstream.runtime_context` config value
 * baked into the config snapshot AlertStreamService is constructed with, so
 * they are kept in dedicated classes rather than mixed into this one.
 */
class RuntimeContextTest extends TestCase
{
    private const ALERT_WEBHOOK = 'https://example.test/runtime-context-teams';

    protected function setUp(): void
    {
        parent::setUp();

        // The provider tags built-in alert channels during register(), which in
        // Testbench runs before defineEnvironment() has applied our config. Force
        // a re-registration now that channels.active is set so the Teams alert
        // channel is discoverable via the 'alertstream.channel' tag.
        $this->app->register(AlertStreamServiceProvider::class, true);
    }

    /**
     * addContext() merges into the bag (later keys overwrite earlier ones),
     * and flushContext() empties it again.
     */
    public function test_add_context_merges_and_flush_resets(): void
    {
        $service = $this->app->make(AlertStreamService::class);

        $service->addContext(['request_id' => 'abc-123', 'tenant' => 'one']);
        $service->addContext(['tenant' => 'two', 'extra' => 'value']);

        $this->assertSame([
            'request_id' => 'abc-123',
            'tenant' => 'two',
            'extra' => 'value',
        ], $service->getRuntimeContext());

        $service->flushContext();

        $this->assertSame([], $service->getRuntimeContext());
    }

    /**
     * $this->app->terminate() flushes the bag (the terminating() callback
     * registered in AlertStreamServiceProvider::registerContextFlushing()).
     */
    public function test_terminating_flushes_the_bag(): void
    {
        $service = $this->app->make(AlertStreamService::class);
        $service->addContext(['request_id' => 'abc-123']);

        $this->assertNotSame([], $service->getRuntimeContext());

        $this->app->terminate();

        $this->assertSame([], $service->getRuntimeContext());
    }

    /**
     * A processed queue job (JobProcessed, which Queue::after() listens for)
     * flushes the bag too, so context never leaks into the next job picked
     * up by a long-lived queue worker process.
     */
    public function test_job_processed_flushes_the_bag(): void
    {
        $service = $this->app->make(AlertStreamService::class);
        $service->addContext(['request_id' => 'abc-123']);

        $this->assertNotSame([], $service->getRuntimeContext());

        event(new JobProcessed('sync', Mockery::mock(Job::class)));

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

        $app['config']->set('alertstream.channels.active', ['teams']);
        $app['config']->set('alertstream.channels.teams.webhook', self::ALERT_WEBHOOK);
    }
}

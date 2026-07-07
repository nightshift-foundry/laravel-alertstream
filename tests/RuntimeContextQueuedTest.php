<?php

namespace NightshiftFoundry\AlertStream\Tests;

use Illuminate\Support\Facades\Event;
use NightshiftFoundry\AlertStream\Events\ExceptionCaptured;
use NightshiftFoundry\AlertStream\Exceptions\Handler;
use NightshiftFoundry\AlertStream\Facades\AlertStream;
use NightshiftFoundry\AlertStream\Providers\AlertStreamServiceProvider;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * End-to-end runtime context behaviour with alertstream.queue enabled.
 *
 * Handler::buildContext() reads AlertStreamService::getConfig('queue', ...)
 * from the config snapshot taken when the service singleton was
 * constructed, so `queue => true` must be set in defineEnvironment() —
 * before that first resolution — rather than mutated mid-test. Kept as a
 * dedicated test class (separate from RuntimeContextTest, which runs with
 * queue => false) so the two configs never collide.
 *
 * With queue on, Handler::handle() fires ExceptionCaptured instead of
 * calling report() directly, so Event::fake() lets us assert on the
 * synchronously-built context without needing a real Teams payload to
 * inspect — TeamsChannel only ever renders context['url'], never arbitrary
 * context keys, so asserting via the queued event is the only way to see
 * runtime context values land in the built context.
 */
class RuntimeContextQueuedTest extends TestCase
{
    private const ALERT_WEBHOOK = 'https://example.test/runtime-context-teams-queued';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(AlertStreamServiceProvider::class, true);
    }

    /**
     * End-to-end: AlertStream::addContext() data shows up in the context of
     * the queued ExceptionCaptured event dispatched by Handler::handle(),
     * proving the bag is read synchronously before queueing occurs.
     */
    public function test_runtime_context_reaches_the_dispatched_event(): void
    {
        Event::fake([ExceptionCaptured::class]);

        AlertStream::addContext(['request_id' => 'abc-123']);

        $this->app->make(Handler::class)->handle(new RuntimeException('boom'));

        Event::assertDispatched(
            ExceptionCaptured::class,
            fn ($event) => ($event->context['request_id'] ?? null) === 'abc-123'
        );
    }

    /**
     * Closure values in the bag are resolved lazily at report time, with the
     * reported exception passed as the argument.
     */
    public function test_closure_value_is_resolved_with_the_exception(): void
    {
        Event::fake([ExceptionCaptured::class]);

        AlertStream::addContext([
            'lazy' => fn ($e) => 'resolved-' . $e->getMessage(),
        ]);

        $this->app->make(Handler::class)->handle(new RuntimeException('boom'));

        Event::assertDispatched(
            ExceptionCaptured::class,
            fn ($event) => ($event->context['lazy'] ?? null) === 'resolved-boom'
        );
    }

    /**
     * A throwing closure must only drop its own key — reporting still
     * proceeds and every other runtime context key survives.
     */
    public function test_throwing_closure_drops_only_its_own_key(): void
    {
        Event::fake([ExceptionCaptured::class]);

        AlertStream::addContext([
            'bad' => fn () => throw new RuntimeException('nope'),
            'good' => 'ok',
        ]);

        $this->app->make(Handler::class)->handle(new RuntimeException('boom'));

        Event::assertDispatched(ExceptionCaptured::class, function ($event) {
            return ($event->context['good'] ?? null) === 'ok'
                && ! array_key_exists('bad', $event->context);
        });
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
        $app['config']->set('alertstream.queue', true);
        $app['config']->set('alertstream.throttle.enabled', false);
        $app['config']->set('alertstream.snapshots.enabled', false);

        $app['config']->set('alertstream.channels.active', ['teams']);
        $app['config']->set('alertstream.channels.teams.webhook', self::ALERT_WEBHOOK);
    }
}

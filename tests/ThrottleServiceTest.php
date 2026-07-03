<?php

namespace NightshiftFoundry\AlertStream\Tests;

use LogicException;
use NightshiftFoundry\AlertStream\Providers\AlertStreamServiceProvider;
use NightshiftFoundry\AlertStream\Services\ThrottleService;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * Verifies the throttle is a fixed-window cap, not a sliding rate limiter:
 * up to `max` occurrences of the same fingerprint are allowed inside a
 * window lasting `cooldown_minutes`, then every further occurrence is
 * suppressed until the window elapses — however far apart the occurrences
 * are in time. Regression test for a bug where the cache TTL was extended
 * on every hit, so exceptions recurring more than a minute apart never
 * reached the cap and were never actually throttled.
 */
class ThrottleServiceTest extends TestCase
{
    public function test_first_occurrence_of_a_fingerprint_is_allowed(): void
    {
        $service = $this->app->make(ThrottleService::class);

        $this->assertTrue($service->allow(new RuntimeException('boom')));
    }

    public function test_occurrences_up_to_max_are_allowed_then_suppressed(): void
    {
        $service = $this->app->make(ThrottleService::class);
        $exception = new RuntimeException('boom');

        // Test config sets max=3.
        $this->assertTrue($service->allow($exception));
        $this->assertTrue($service->allow($exception));
        $this->assertTrue($service->allow($exception));
        $this->assertFalse($service->allow($exception));
        $this->assertFalse($service->allow($exception));
    }

    /**
     * The window is fixed at the first hit and must not be extended by
     * later hits — otherwise a steady trickle of occurrences slower than
     * the window length would never reach `max` and would never throttle,
     * which was the original bug.
     */
    public function test_occurrences_spaced_out_still_count_toward_the_same_fixed_window(): void
    {
        $this->app['config']->set('alertstream.throttle.cooldown_minutes', 10);
        $service = $this->app->make(ThrottleService::class);
        $exception = new RuntimeException('boom');

        $this->assertTrue($service->allow($exception));

        $this->travel(4)->minutes();
        $this->assertTrue($service->allow($exception));

        $this->travel(4)->minutes(); // 8 minutes since the first hit — window (10m) still open
        $this->assertTrue($service->allow($exception));

        $this->travel(1)->minutes(); // 9 minutes since the first hit — max (3) already reached
        $this->assertFalse($service->allow($exception));
    }

    public function test_repeat_occurrence_after_the_window_expires_is_allowed_again(): void
    {
        $this->app['config']->set('alertstream.throttle.max', 1);
        $this->app['config']->set('alertstream.throttle.cooldown_minutes', 1);
        $service = $this->app->make(ThrottleService::class);
        $exception = new RuntimeException('boom');

        $this->assertTrue($service->allow($exception));
        $this->assertFalse($service->allow($exception));

        $this->travel(2)->minutes();

        $this->assertTrue($service->allow($exception));
    }

    public function test_different_fingerprints_are_throttled_independently(): void
    {
        $service = $this->app->make(ThrottleService::class);

        $this->assertTrue($service->allow(new RuntimeException('boom')));
        $this->assertTrue($service->allow(new LogicException('different exception type')));
    }

    public function test_throttling_disabled_always_allows(): void
    {
        $this->app['config']->set('alertstream.throttle.enabled', false);
        $service = $this->app->make(ThrottleService::class);
        $exception = new RuntimeException('boom');

        $this->assertTrue($service->allow($exception));
        $this->assertTrue($service->allow($exception));
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
        $app['config']->set('alertstream.throttle.enabled', true);
        $app['config']->set('alertstream.throttle.max', 3);
        $app['config']->set('alertstream.throttle.cooldown_minutes', 60);
    }
}

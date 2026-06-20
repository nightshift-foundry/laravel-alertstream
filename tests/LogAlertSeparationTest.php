<?php

namespace NightshiftFoundry\AlertStream\Tests;

use Illuminate\Support\Facades\Http;
use NightshiftFoundry\AlertStream\Providers\AlertStreamServiceProvider;
use NightshiftFoundry\AlertStream\Services\AlertStreamService;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * Verifies that the exception pipeline (report()) and the log pipeline (log())
 * use two separate delivery paths and never share a webhook.
 */
class LogAlertSeparationTest extends TestCase
{
    private const ALERT_WEBHOOK = 'https://example.test/alert-teams';

    private const LOG_WEBHOOK = 'https://example.test/log-teams';

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
     * report() must post to the ALERT webhook and never the LOG webhook.
     */
    public function test_report_dispatches_only_to_alert_channel(): void
    {
        Http::fake([
            '*' => Http::response('ok', 200),
        ]);

        $this->app->make(AlertStreamService::class)
            ->report('Boom', new RuntimeException('boom'), ['order_id' => 7]);

        Http::assertSent(fn ($request) => $request->url() === self::ALERT_WEBHOOK);
        Http::assertNotSent(fn ($request) => $request->url() === self::LOG_WEBHOOK);

        $this->assertSame(1, $this->countRequestsTo(self::ALERT_WEBHOOK));
        $this->assertSame(0, $this->countRequestsTo(self::LOG_WEBHOOK));
    }

    /**
     * log() must post to the LOG webhook and never the ALERT webhook.
     */
    public function test_log_dispatches_only_to_log_channel(): void
    {
        Http::fake([
            '*' => Http::response('ok', 200),
        ]);

        $this->app->make(AlertStreamService::class)
            ->log('info', 'hello');

        Http::assertSent(fn ($request) => $request->url() === self::LOG_WEBHOOK);
        Http::assertNotSent(fn ($request) => $request->url() === self::ALERT_WEBHOOK);

        $this->assertSame(1, $this->countRequestsTo(self::LOG_WEBHOOK));
        $this->assertSame(0, $this->countRequestsTo(self::ALERT_WEBHOOK));
    }

    /**
     * When the log webhook is unset, log() falls back to the alert webhook.
     */
    public function test_log_falls_back_to_alert_webhook_when_log_webhook_unset(): void
    {
        $this->app['config']->set('alertstream.log_destinations.teams.webhook', null);
        $this->app['config']->set('alertstream.channels.teams.webhook', self::ALERT_WEBHOOK);

        Http::fake([
            '*' => Http::response('ok', 200),
        ]);

        $this->app->make(AlertStreamService::class)
            ->log('info', 'hi');

        Http::assertSent(fn ($request) => $request->url() === self::ALERT_WEBHOOK);
        $this->assertSame(1, $this->countRequestsTo(self::ALERT_WEBHOOK));
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
        // Set config BEFORE the provider registers so registerBuiltInChannels()
        // tags the Teams alert channel and report() can discover it.
        $app['config']->set('alertstream.enabled', true);
        $app['config']->set('alertstream.queue', false);
        $app['config']->set('alertstream.throttle.enabled', false);
        $app['config']->set('alertstream.snapshots.enabled', false);

        // Alert pipeline: Teams active, webhook A.
        $app['config']->set('alertstream.channels.active', ['teams']);
        $app['config']->set('alertstream.channels.teams.webhook', self::ALERT_WEBHOOK);

        // Log pipeline: Teams selected, distinct webhook B.
        $app['config']->set('alertstream.log_channels', ['teams']);
        $app['config']->set('alertstream.log_destinations.teams.webhook', self::LOG_WEBHOOK);
    }

    /**
     * Count the faked HTTP requests sent to a given URL.
     */
    private function countRequestsTo(string $url): int
    {
        return Http::recorded(fn ($request) => $request->url() === $url)->count();
    }
}

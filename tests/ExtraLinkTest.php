<?php

namespace NightshiftFoundry\AlertStream\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use NightshiftFoundry\AlertStream\Providers\AlertStreamServiceProvider;
use NightshiftFoundry\AlertStream\Services\AlertStreamService;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * Verifies the global `extra_link` config block is rendered alongside the
 * existing snapshot link in every built-in alert channel message.
 */
class ExtraLinkTest extends TestCase
{
    private const TEAMS_WEBHOOK = 'https://example.test/extra-link-teams';

    private const SLACK_WEBHOOK = 'https://example.test/extra-link-slack';

    private const EXTRA_LINK_URL = 'https://example.test/runbook';

    private const EXTRA_LINK_TEXT = 'Open Runbook';

    protected function setUp(): void
    {
        parent::setUp();

        // The provider tags built-in alert channels during register(), which in
        // Testbench runs before defineEnvironment() has applied our config. Force
        // a re-registration now that channels.active is set so both alert
        // channels are discoverable via the 'alertstream.channel' tag.
        $this->app->register(AlertStreamServiceProvider::class, true);
    }

    /**
     * report() renders the configured extra link (URL + display text) in
     * both the Teams and Slack channel payloads.
     */
    public function test_extra_link_is_rendered_on_teams_and_slack(): void
    {
        Http::fake([
            '*' => Http::response('ok', 200),
        ]);

        $this->app->make(AlertStreamService::class)
            ->report('Boom', new RuntimeException('boom'));

        Http::assertSent(function ($request) {
            if ($request->url() !== self::TEAMS_WEBHOOK) {
                return false;
            }

            $body = $request->data()['message'] ?? '';

            return str_contains($body, self::EXTRA_LINK_URL)
                && str_contains($body, self::EXTRA_LINK_TEXT);
        });

        Http::assertSent(function ($request) {
            if ($request->url() !== self::SLACK_WEBHOOK) {
                return false;
            }

            return str_contains(
                $this->slackBlockText($request),
                '<' . self::EXTRA_LINK_URL . '|' . self::EXTRA_LINK_TEXT . '>'
            );
        });
    }

    /**
     * When no extra_link URL is configured, neither channel payload
     * references it at all.
     */
    public function test_no_extra_link_rendered_when_url_is_unset(): void
    {
        $this->app['config']->set('alertstream.extra_link.url', null);
        $this->app->register(AlertStreamServiceProvider::class, true);

        Http::fake([
            '*' => Http::response('ok', 200),
        ]);

        $this->app->make(AlertStreamService::class)
            ->report('Boom', new RuntimeException('boom'));

        Http::assertSent(function ($request) {
            if ($request->url() !== self::TEAMS_WEBHOOK) {
                return false;
            }

            $body = $request->data()['message'] ?? '';

            return ! str_contains($body, 'example.test/runbook');
        });

        Http::assertSent(function ($request) {
            if ($request->url() !== self::SLACK_WEBHOOK) {
                return false;
            }

            return ! str_contains($this->slackBlockText($request), 'example.test/runbook');
        });
    }

    /**
     * When the URL is set but the display text is null/empty, the channel
     * falls back to the default text 'More information'.
     */
    public function test_extra_link_text_falls_back_to_default_when_empty(): void
    {
        $this->app['config']->set('alertstream.extra_link.text', '');
        $this->app->register(AlertStreamServiceProvider::class, true);

        Http::fake([
            '*' => Http::response('ok', 200),
        ]);

        $this->app->make(AlertStreamService::class)
            ->report('Boom', new RuntimeException('boom'));

        Http::assertSent(function ($request) {
            if ($request->url() !== self::TEAMS_WEBHOOK) {
                return false;
            }

            $body = $request->data()['message'] ?? '';

            return str_contains($body, 'More information');
        });

        Http::assertSent(function ($request) {
            if ($request->url() !== self::SLACK_WEBHOOK) {
                return false;
            }

            return str_contains(
                $this->slackBlockText($request),
                '<' . self::EXTRA_LINK_URL . '|More information>'
            );
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
        $app['config']->set('alertstream.queue', false);
        $app['config']->set('alertstream.throttle.enabled', false);
        $app['config']->set('alertstream.snapshots.enabled', false);

        // Teams + Slack active with fake webhook URLs.
        $app['config']->set('alertstream.channels.active', ['teams', 'slack']);
        $app['config']->set('alertstream.channels.teams.webhook', self::TEAMS_WEBHOOK);
        $app['config']->set('alertstream.channels.slack.webhook', self::SLACK_WEBHOOK);

        $app['config']->set('alertstream.extra_link.url', self::EXTRA_LINK_URL);
        $app['config']->set('alertstream.extra_link.text', self::EXTRA_LINK_TEXT);
    }

    /**
     * Concatenates every mrkdwn `text` string across the Slack payload's
     * `blocks` array. Reads the already-JSON-decoded request data directly
     * (rather than re-encoding it) so slash-escaping differences between the
     * original wire body and a fresh json_encode() never cause a false
     * mismatch.
     */
    private function slackBlockText(Request $request): string
    {
        $blocks = $request->data()['blocks'] ?? [];

        return collect($blocks)
            ->flatMap(fn ($block) => [
                $block['text']['text'] ?? null,
                ...collect($block['fields'] ?? [])->pluck('text'),
            ])
            ->filter()
            ->implode(' | ');
    }
}

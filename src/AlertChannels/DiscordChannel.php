<?php

namespace NightshiftFoundry\AlertStream\AlertChannels;

use Illuminate\Http\Client\Factory as HttpClient;
use NightshiftFoundry\AlertStream\AlertChannels\Contracts\AlertChannel;
use Throwable;

class DiscordChannel implements AlertChannel
{
    public function __construct(
        protected array $config,
        protected HttpClient $http
    ) {
    }

    /**
     * @inheritDoc
     */
    public function send(string $title, Throwable $exception, array $context, ?string $snapshotUrl = null): void
    {
        if (empty($this->config['webhook'])) {
            return;
        }

        $severity = $context['severity'] ?? 'error';
        $colour = match ($severity) {
            'critical' => 16711680, // red
            'warning' => 16753920, // orange
            default => 16737792, // dark orange
        };

        $fields = [
            ['name' => 'Message',     'value' => '```' . $exception->getMessage() . '```', 'inline' => false],
            ['name' => 'File',        'value' => '`' . $exception->getFile() . ':' . $exception->getLine() . '`', 'inline' => false],
            ['name' => 'Severity',    'value' => ucfirst($severity), 'inline' => true],
            ['name' => 'Environment', 'value' => app()->environment() ?? 'unknown', 'inline' => true],
        ];

        if (! empty($context['url'])) {
            $fields[] = ['name' => 'URL', 'value' => $context['method'] . ' ' . $context['url'], 'inline' => false];
        }

        if ($snapshotUrl) {
            $fields[] = ['name' => 'Snapshot', 'value' => '[View Full Stacktrace](' . $snapshotUrl . ')', 'inline' => false];
        }

        $this->http->retry(2, 100)->post($this->config['webhook'], [
            'embeds' => [
                [
                    'title' => $title,
                    'color' => $colour,
                    'fields' => $fields,
                    'footer' => ['text' => 'AlertStream • ' . now()->toDateTimeString()],
                ],
            ],
        ]);
    }
}

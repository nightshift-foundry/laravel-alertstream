<?php

namespace NightshiftFoundry\AlertStream\Channels;

use Illuminate\Http\Client\Factory as HttpClient;
use NightshiftFoundry\AlertStream\Channels\Contracts\AlertChannel;
use Throwable;

class SlackChannel implements AlertChannel
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
        $emoji = match ($severity) {
            'critical' => '🔴',
            'warning' => '🟡',
            default => '🟠',
        };

        $fields = [
            [
                'type' => 'mrkdwn',
                'text' => '*Message:*' . "\n" . $exception->getMessage(),
            ],
            [
                'type' => 'mrkdwn',
                'text' => '*File:*' . "\n" . $exception->getFile() . ':' . $exception->getLine(),
            ],
            [
                'type' => 'mrkdwn',
                'text' => '*Severity:*' . "\n" . ucfirst($severity),
            ],
            [
                'type' => 'mrkdwn',
                'text' => '*Environment:*' . "\n" . (app()->environment() ?? 'unknown'),
            ],
        ];

        if (! empty($context['url'])) {
            $fields[] = [
                'type' => 'mrkdwn',
                'text' => '*URL:*' . "\n" . $context['method'] . ' ' . $context['url'],
            ];
        }

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => $emoji . ' ' . $title,
                ],
            ],
            [
                'type' => 'section',
                'fields' => $fields,
            ],
        ];

        if ($snapshotUrl) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '🔗 <' . $snapshotUrl . '|View Full Stacktrace>',
                ],
            ];
        }

        $blocks[] = [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => 'AlertStream • ' . now()->toDateTimeString(),
                ],
            ],
        ];

        $this->http->retry(2, 100)->post($this->config['webhook'], [
            'blocks' => $blocks,
        ]);
    }
}

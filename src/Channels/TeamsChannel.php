<?php

namespace NightshiftFoundry\AlertStream\Channels;

use Illuminate\Http\Client\Factory as HttpClient;
use NightshiftFoundry\AlertStream\Channels\Contracts\AlertChannel;
use Throwable;

class TeamsChannel implements AlertChannel
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

        $facts = [
            ['title' => 'Message',     'value' => $exception->getMessage()],
            ['title' => 'File',        'value' => $exception->getFile() . ':' . $exception->getLine()],
            ['title' => 'Severity',    'value' => ucfirst($severity)],
            ['title' => 'Environment', 'value' => app()->environment() ?? 'unknown'],
        ];

        if (! empty($context['url'])) {
            $facts[] = ['title' => 'URL', 'value' => ($context['method'] ?? 'GET') . ' ' . $context['url']];
        }

        $cardBody = [
            [
                'type' => 'TextBlock',
                'text' => $title,
                'wrap' => true,
                'weight' => 'Bolder',
                'size' => 'Medium',
            ],
            [
                'type' => 'TextBlock',
                'text' => 'Captured at ' . now()->toDateTimeString(),
                'wrap' => true,
                'spacing' => 'Small',
                'isSubtle' => true,
            ],
            [
                'type' => 'FactSet',
                'facts' => $facts,
                'spacing' => 'Medium',
            ],
        ];

        $card = [
            '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
            'type' => 'AdaptiveCard',
            'version' => '1.2',
            'body' => $cardBody,
        ];

        if ($snapshotUrl) {
            $card['actions'] = [
                [
                    'type' => 'Action.OpenUrl',
                    'title' => 'View Full Stacktrace',
                    'url' => $snapshotUrl,
                ],
            ];
        }

        $payload = [
            'type' => 'message',
            'Attachments' => true,  // capital A — satisfies PA flow null-check, routes to forEach branch
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'contentUrl' => null,
                    'content' => $card,
                ],
            ],
        ];

        try {
            $this->http->timeout(5)->retry(2, 200)->post($this->config['webhook'], $payload);
        } catch (Throwable) {
            // Swallow send errors — alerting must never crash the application.
        }
    }
}

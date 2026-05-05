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

        $severityStyle = match (strtolower($severity)) {
            'critical', 'emergency', 'alert', 'error' => 'attention',
            'warning' => 'warning',
            'notice', 'info' => 'accent',
            default => 'emphasis',
        };

        $severityColor = match (strtolower($severity)) {
            'critical', 'emergency', 'alert', 'error' => 'attention',
            'warning' => 'warning',
            'notice', 'info' => 'accent',
            default => 'default',
        };

        $severityEmoji = match (strtolower($severity)) {
            'critical', 'emergency', 'alert' => '🔴',
            'error' => '🚨',
            'warning' => '⚠️',
            'notice', 'info' => 'ℹ️',
            default => '🔵',
        };

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
            // ── Coloured header band ──────────────────────────────────────
            [
                'type' => 'Container',
                'style' => $severityStyle,
                'bleed' => true,
                'spacing' => 'None',
                'items' => [
                    [
                        'type' => 'ColumnSet',
                        'columns' => [
                            [
                                'type' => 'Column',
                                'width' => 'stretch',
                                'items' => [
                                    [
                                        'type' => 'TextBlock',
                                        'text' => $severityEmoji . '  ' . $title,
                                        'wrap' => true,
                                        'weight' => 'Bolder',
                                        'size' => 'Large',
                                        'color' => 'Light',
                                    ],
                                    [
                                        'type' => 'TextBlock',
                                        'text' => 'Captured at ' . now()->toDateTimeString(),
                                        'wrap' => true,
                                        'size' => 'Small',
                                        'color' => 'Light',
                                        'spacing' => 'None',
                                        'isSubtle' => true,
                                    ],
                                ],
                            ],
                            [
                                'type' => 'Column',
                                'width' => 'auto',
                                'verticalContentAlignment' => 'Center',
                                'items' => [
                                    [
                                        'type' => 'TextBlock',
                                        'text' => strtoupper($severity),
                                        'weight' => 'Bolder',
                                        'size' => 'Small',
                                        'color' => 'Light',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ── Exception message ─────────────────────────────────────────
            [
                'type' => 'Container',
                'style' => 'emphasis',
                'spacing' => 'Medium',
                'separator' => true,
                'items' => [
                    [
                        'type' => 'TextBlock',
                        'text' => 'EXCEPTION',
                        'weight' => 'Bolder',
                        'size' => 'Small',
                        'color' => $severityColor,
                        'spacing' => 'None',
                    ],
                    [
                        'type' => 'TextBlock',
                        'text' => $exception->getMessage(),
                        'wrap' => true,
                        'spacing' => 'Small',
                        'isSubtle' => false,
                    ],
                ],
            ],

            // ── Details fact-set ──────────────────────────────────────────
            [
                'type' => 'Container',
                'spacing' => 'Medium',
                'separator' => true,
                'items' => [
                    [
                        'type' => 'TextBlock',
                        'text' => 'DETAILS',
                        'weight' => 'Bolder',
                        'size' => 'Small',
                        'color' => $severityColor,
                        'spacing' => 'None',
                    ],
                    [
                        'type' => 'FactSet',
                        'facts' => array_filter($facts, fn ($f) => $f['title'] !== 'Message'),
                        'spacing' => 'Small',
                    ],
                ],
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
            'summary' => '🚨 ' . $title . ' — ' . $exception->getMessage(),
            'text' => '[' . ucfirst($severity) . '] ' . $title . ': ' . $exception->getMessage(),
            'Attachments' => true,
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

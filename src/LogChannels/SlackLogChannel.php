<?php

namespace NightshiftFoundry\AlertStream\LogChannels;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Config;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use NightshiftFoundry\AlertStream\LogChannels\Contracts\LogChannel;
use Throwable;

class SlackLogChannel extends AbstractProcessingHandler implements LogChannel
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('alertstream_slack');

        $this->setLevel(Level::fromName($config['level'] ?? 'debug'));

        $logger->pushHandler($this);

        return $logger;
    }

    protected function write(LogRecord $record): void
    {
        $webhook = Config::get('alertstream.log_destinations.slack.webhook');

        if (empty($webhook)) {
            return;
        }

        $level = strtolower($record->level->name);

        $emoji = match ($level) {
            'critical', 'emergency', 'alert' => '🔴',
            'error' => '🚨',
            'warning' => '⚠️',
            'notice', 'info' => 'ℹ️',
            'debug' => '🟢',
            default => '🔵',
        };

        $fields = [
            [
                'type' => 'mrkdwn',
                'text' => '*Level:*' . "\n" . strtoupper($level),
            ],
            [
                'type' => 'mrkdwn',
                'text' => '*Time:*' . "\n" . $record->datetime->format('Y-m-d H:i:s'),
            ],
        ];

        if (! empty($record->context)) {
            $contextLines = [];
            foreach ($record->context as $key => $value) {
                $contextLines[] = '*' . $key . ':* ' . (is_string($value) ? $value : json_encode($value));
            }
            $fields[] = [
                'type' => 'mrkdwn',
                'text' => '*Context:*' . "\n" . implode("\n", $contextLines),
            ];
        }

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => $emoji . ' ' . $record->message,
                ],
            ],
            [
                'type' => 'section',
                'fields' => $fields,
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => 'AlertStream • ' . $record->datetime->format('Y-m-d H:i:s'),
                    ],
                ],
            ],
        ];

        try {
            $http = app(HttpClient::class);
            $http->timeout(5)->retry(2, 200)->post($webhook, ['blocks' => $blocks]);
        } catch (Throwable) {
            // Swallow send errors — logging must never crash the application.
        }
    }
}

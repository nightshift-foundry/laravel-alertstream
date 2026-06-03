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

class DiscordLogChannel extends AbstractProcessingHandler implements LogChannel
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('alertstream_discord');

        $this->setLevel(Level::fromName($config['level'] ?? 'debug'));

        $logger->pushHandler($this);

        return $logger;
    }

    protected function write(LogRecord $record): void
    {
        $webhook = Config::get('alertstream.log_destinations.discord.webhook')
            ?: Config::get('alertstream.channels.discord.webhook');

        if (empty($webhook)) {
            return;
        }

        $level = strtolower($record->level->name);

        $colour = match ($level) {
            'critical', 'emergency', 'alert' => 9109504,  // dark red
            'error' => 13369344,                           // red
            'warning' => 15098112,                         // orange
            'notice', 'info' => 30420,                     // blue
            'debug' => 1081344,                            // green
            default => 6710886,                            // grey
        };

        $fields = [
            ['name' => 'Level',  'value' => strtoupper($level),                         'inline' => true],
            ['name' => 'Time',   'value' => $record->datetime->format('Y-m-d H:i:s'),   'inline' => true],
        ];

        foreach ($record->context as $key => $value) {
            $fields[] = [
                'name' => $key,
                'value' => is_string($value) ? $value : json_encode($value),
                'inline' => false,
            ];
        }

        try {
            $http = new HttpClient();
            $http->timeout(5)->retry(2, 200)->post($webhook, [
                'embeds' => [
                    [
                        'title' => $record->message,
                        'color' => $colour,
                        'fields' => $fields,
                        'footer' => ['text' => 'AlertStream • ' . $record->datetime->format('Y-m-d H:i:s')],
                    ],
                ],
            ]);
        } catch (Throwable) {
            // Swallow send errors — logging must never crash the application.
        }
    }
}

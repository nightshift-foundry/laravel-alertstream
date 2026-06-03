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

class TeamsLogChannel extends AbstractProcessingHandler implements LogChannel
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('alertstream_teams');

        $this->setLevel(Level::fromName($config['level'] ?? 'debug'));

        $logger->pushHandler($this);

        return $logger;
    }

    protected function write(LogRecord $record): void
    {
        $webhook = Config::get('alertstream.log_destinations.teams.webhook')
            ?: Config::get('alertstream.channels.teams.webhook');

        if (empty($webhook)) {
            return;
        }

        $level = strtolower($record->level->name);
        $datetime = $record->datetime->format('Y-m-d H:i:s');
        $message = htmlspecialchars($record->message, ENT_QUOTES | ENT_HTML5);
        $env = app()->environment() ?? 'unknown';

        $color = match ($level) {
            'critical', 'emergency', 'alert' => '#8B0000',
            'error' => '#CC0000',
            'warning' => '#E65C00',
            'notice', 'info' => '#0078D4',
            'debug' => '#107C10',
            default => '#666666',
        };

        $emoji = match ($level) {
            'critical', 'emergency', 'alert' => '🔴',
            'error' => '🚨',
            'warning' => '⚠️',
            'notice', 'info' => 'ℹ️',
            'debug' => '🟢',
            default => '🔵',
        };

        $html = '<div>';
        $html .= '<h2 style="color:' . $color . ';">' . $emoji . ' Log Message</h2>';
        $html .= '<p style="color:#666;font-size:12px;">Triggered at ' . $datetime . ' &bull; <strong style="color:' . $color . ';">' . strtoupper($level) . '</strong></p>';
        $html .= '<hr/>';
        $html .= '<p>' . $message . '</p>';
        $html .= '<hr/>';

        $html .= '<table>';
        $html .= '<tr><td><strong>Environment</strong></td><td>' . htmlspecialchars($env, ENT_QUOTES | ENT_HTML5) . '</td></tr>';

        foreach ($record->context as $key => $value) {
            $html .= '<tr><td><strong>' . htmlspecialchars($key, ENT_QUOTES | ENT_HTML5) . '</strong></td>';
            $html .= '<td>' . htmlspecialchars(is_string($value) ? $value : json_encode($value), ENT_QUOTES | ENT_HTML5) . '</td></tr>';
        }

        $html .= '</table>';
        $html .= '</div>';

        try {
            $http = new HttpClient();
            $http->timeout(5)->retry(2, 200)->post($webhook, ['message' => $html]);
        } catch (Throwable) {
            // Swallow send errors — logging must never crash the application.
        }
    }
}

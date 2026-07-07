<?php

namespace NightshiftFoundry\AlertStream\AlertChannels;

use Illuminate\Http\Client\Factory as HttpClient;
use NightshiftFoundry\AlertStream\AlertChannels\Contracts\AlertChannel;
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
        $severityLower = strtolower($severity);

        // ── Severity → hex color ───────────────────────────────────────────────
        $color = match ($severityLower) {
            'critical', 'emergency', 'alert' => '#8B0000', // dark red
            'error' => '#CC0000', // red
            'warning' => '#E65C00', // orange
            'notice', 'info' => '#0078D4', // Teams blue
            'debug' => '#107C10', // green
            default => '#666666', // grey
        };

        // ── Severity → emoji ───────────────────────────────────────────────────
        $emoji = match ($severityLower) {
            'critical', 'emergency', 'alert' => '🔴',
            'error' => '🚨',
            'warning' => '⚠️',
            'notice', 'info' => 'ℹ️',
            'debug' => '🟢',
            default => '🔵',
        };

        $env = app()->environment() ?? 'unknown';
        $triggeredAt = now(config('app.timezone'))->toDateTimeString();
        $file = $exception->getFile() . ':' . $exception->getLine();
        $exClass = class_basename($exception);
        $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_HTML5);

        // ── HTML message body ──────────────────────────────────────────────────
        $html = '<div>';

        // Header
        $html .= '<h2 style="color:' . $color . ';">' . $emoji . ' ' . htmlspecialchars($title, ENT_QUOTES | ENT_HTML5) . '</h2>';
        $html .= '<p style="color:#666;font-size:12px;">Triggered at ' . $triggeredAt . ' &bull; <strong style="color:' . $color . ';">' . strtoupper($severity) . '</strong></p>';
        $html .= '<hr/>';

        // Exception class + message
        $html .= '<p><strong style="color:' . $color . ';">' . htmlspecialchars($exClass, ENT_QUOTES | ENT_HTML5) . '</strong></p>';
        $html .= '<p>' . $message . '</p>';
        $html .= '<hr/>';

        // Details table
        $html .= '<table>';
        $html .= '<tr><td><strong>File</strong></td><td><code>' . htmlspecialchars($file, ENT_QUOTES | ENT_HTML5) . '</code></td></tr>';
        $html .= '<tr><td><strong>Severity</strong></td><td style="color:' . $color . ';"><strong>' . ucfirst($severity) . '</strong></td></tr>';
        $html .= '<tr><td><strong>Environment</strong></td><td>' . htmlspecialchars($env, ENT_QUOTES | ENT_HTML5) . '</td></tr>';

        if (! empty($context['url'])) {
            $url = htmlspecialchars(($context['method'] ?? 'GET') . ' ' . $context['url'], ENT_QUOTES | ENT_HTML5);
            $html .= '<tr><td><strong>URL</strong></td><td><code>' . $url . '</code></td></tr>';
        }

        $html .= '</table>';

        // Stacktrace link
        if ($snapshotUrl) {
            $html .= '<hr/>';
            $html .= '<p>🔗 <a href="' . htmlspecialchars($snapshotUrl, ENT_QUOTES | ENT_HTML5) . '"><strong>View Full Stacktrace</strong></a></p>';
        }

        // Extra link (optional, configured globally for every alert channel)
        if (! empty($this->config['extra_link']['url'])) {
            $extraUrl = $this->config['extra_link']['url'];
            $extraText = ! empty($this->config['extra_link']['text']) ? $this->config['extra_link']['text'] : 'More information';

            // Only emit a separating <hr/> here if the snapshot block above
            // didn't already emit one before its own link.
            if (! $snapshotUrl) {
                $html .= '<hr/>';
            }

            $html .= '<p>🔗 <a href="' . htmlspecialchars($extraUrl, ENT_QUOTES | ENT_HTML5) . '"><strong>' . htmlspecialchars($extraText, ENT_QUOTES | ENT_HTML5) . '</strong></a></p>';
        }

        $html .= '</div>';

        $payload = [
            'message' => $html,
        ];

        try {
            $this->http->timeout(5)->retry(2, 200)->post($this->config['webhook'], $payload);
        } catch (Throwable) {
            // Swallow send errors — alerting must never crash the application.
        }
    }
}

<?php

namespace NightshiftFoundry\AlertStream\LogChannels;

use Illuminate\Support\Facades\Config;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use NightshiftFoundry\AlertStream\LogChannels\Contracts\LogChannel;
use Throwable;

class MailLogChannel extends AbstractProcessingHandler implements LogChannel
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('alertstream_mail');

        $this->setLevel(Level::fromName($config['level'] ?? 'debug'));

        $logger->pushHandler($this);

        return $logger;
    }

    protected function write(LogRecord $record): void
    {
        $logDest = Config::get('alertstream.log_destinations.mail', []);
        $alertDest = Config::get('alertstream.channels.mail', []);

        $config = [
            'to' => $logDest['to'] ?: ($alertDest['to'] ?? null),
            'from' => $logDest['from'] ?: ($alertDest['from'] ?? null),
        ];

        if (empty($config['to'])) {
            return;
        }

        $level = strtolower($record->level->name);
        $datetime = $record->datetime->format('Y-m-d H:i:s');
        $message = htmlspecialchars($record->message, ENT_QUOTES | ENT_HTML5);
        $subject = '[AlertStream] [' . strtoupper($level) . '] ' . $record->message;
        $from = $config['from'] ?? Config::get('mail.from.address');

        $html = '<div style="font-family:sans-serif;">';
        $html .= '<h2>[' . strtoupper($level) . '] Log Message</h2>';
        $html .= '<p style="color:#666;">' . $datetime . '</p>';
        $html .= '<hr/>';
        $html .= '<p>' . $message . '</p>';

        if (! empty($record->context)) {
            $html .= '<hr/><h3>Context</h3>';
            $html .= '<table border="1" cellpadding="4" style="border-collapse:collapse;">';
            foreach ($record->context as $key => $value) {
                $html .= '<tr>';
                $html .= '<td><strong>' . htmlspecialchars($key, ENT_QUOTES | ENT_HTML5) . '</strong></td>';
                $html .= '<td>' . htmlspecialchars(is_string($value) ? $value : json_encode($value), ENT_QUOTES | ENT_HTML5) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        $html .= '</div>';

        try {
            app('mailer')->html($html, function ($msg) use ($config, $subject, $from): void {
                $msg->to($config['to'])->subject($subject);
                if ($from) {
                    $msg->from($from);
                }
            });
        } catch (Throwable) {
            // Swallow send errors — logging must never crash the application.
        }
    }
}

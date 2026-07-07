<?php

namespace NightshiftFoundry\AlertStream\AlertChannels;

use Illuminate\Contracts\Mail\Mailer;
use NightshiftFoundry\AlertStream\AlertChannels\Contracts\AlertChannel;
use Throwable;

class MailChannel implements AlertChannel
{
    public function __construct(
        protected array $config,
        protected Mailer $mailer
    ) {
    }

    /**
     * @inheritDoc
     */
    public function send(string $title, Throwable $exception, array $context, ?string $snapshotUrl = null): void
    {
        if (empty($this->config['to'])) {
            return;
        }

        $severity = $context['severity'] ?? 'error';
        $env = app()->environment() ?? 'unknown';
        $timestamp = now()->toDateTimeString();
        $from = $this->config['from'] ?? config('mail.from.address');

        $html = view('alertstream::emails.alertstream', [
            'title' => $title,
            'severity' => $severity,
            'env' => $env,
            'timestamp' => $timestamp,
            'exceptionClass' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context,
            'snapshotUrl' => $snapshotUrl,
            'extraLink' => $this->config['extra_link'] ?? [],
        ])->render();

        $this->mailer
            ->html($html, function ($message) use ($title, $from): void {
                $message->to($this->config['to'])
                    ->subject('[AlertStream] ' . $title);

                if ($from) {
                    $message->from($from);
                }
            });
    }
}

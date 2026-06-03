<?php

namespace NightshiftFoundry\AlertStream\AlertChannels;

use Illuminate\Notifications\Notification;
use NightshiftFoundry\AlertStream\Services\AlertStreamService;

class AlertStreamNotificationChannel
{
    public function __construct(protected AlertStreamService $alertStream)
    {
    }

    /**
     * Send the given notification via AlertStream.
     *
     * The notification must implement toAlertStream() returning an array:
     *   [
     *       'message'   => 'Something happened',
     *       'exception' => $throwable,     // optional
     *       'context'   => [],             // optional
     *   ]
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toAlertStream')) {
            return;
        }

        $data = $notification->toAlertStream($notifiable);
        $message = $data['message'] ?? 'Notification: ' . class_basename($notification);
        $exception = $data['exception'] ?? null;
        $context = $data['context'] ?? [];

        $this->alertStream->report($message, $exception, $context);
    }
}

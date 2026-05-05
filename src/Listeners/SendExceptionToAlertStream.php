<?php

namespace NightshiftFoundry\AlertStream\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use NightshiftFoundry\AlertStream\Events\ExceptionCaptured;
use NightshiftFoundry\AlertStream\Services\AlertStreamService;
use Throwable;

class SendExceptionToAlertStream implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue connection to use.
     *
     * Pulled from alertstream.queue_connection (falls back to the app default).
     *
     * @var string|null
     */
    public ?string $connection;

    /**
     * The name of the queue to dispatch to.
     *
     * Pulled from alertstream.queue_name (falls back to the queue default).
     *
     * @var string|null
     */
    public ?string $queue;

    /**
     * Run the listener after any pending database transactions are committed
     * so the listener always has a consistent application state.
     *
     * @var bool
     */
    public bool $afterCommit = true;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * Create the listener.
     *
     * @param AlertStreamService $alertStream
     */
    public function __construct(protected AlertStreamService $alertStream)
    {
        $this->connection = $this->alertStream->getConfig('queue_connection');
        $this->queue = $this->alertStream->getConfig('queue_name');
    }

    /**
     * Handle the ExceptionCaptured event.
     *
     * Runs on a queue worker — completely off the hot path.
     *
     * @param ExceptionCaptured $event
     */
    public function handle(ExceptionCaptured $event): void
    {
        try {
            $this->alertStream->report(
                $event->title,
                $event->exception,
                $event->context,
            );
        } catch (Throwable) {
            // Silently fail — never let the listener crash the queue worker
            // with an infinite-retry loop for a reporting side-effect.
        }
    }
}

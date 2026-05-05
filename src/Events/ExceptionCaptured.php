<?php

namespace NightshiftFoundry\AlertStream\Events;

use Throwable;

class ExceptionCaptured
{
    /**
     * The captured exception.
     *
     * @var Throwable
     */
    public Throwable $exception;

    /**
     * Human-readable title (e.g. "Exception: RuntimeException").
     *
     * @var string
     */
    public string $title;

    /**
     * Pre-built context array.
     *
     * Collected synchronously in the Handler while the request is still in
     * scope, before the event is dispatched to the queue.
     *
     * @var array
     */
    public array $context;

    /**
     * Create a new ExceptionCaptured event.
     *
     * @param Throwable $exception
     * @param string $title
     * @param array $context
     */
    public function __construct(Throwable $exception, string $title, array $context = [])
    {
        $this->exception = $exception;
        $this->title = $title;
        $this->context = $context;
    }
}

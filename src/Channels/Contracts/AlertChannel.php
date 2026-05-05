<?php

namespace NightshiftFoundry\AlertStream\Channels\Contracts;

use Throwable;

interface AlertChannel
{
    /**
     * Deliver an alert through this channel.
     *
     * Implementations must be silent on failure — never throw, never crash
     * the application.  Swallow exceptions internally and log them locally
     * if needed.
     *
     * @param string $title Human-readable title, e.g. "Exception: RuntimeException"
     * @param Throwable $exception The captured exception
     * @param array $context Pre-built context (url, user_id, severity, …)
     * @param string|null $snapshotUrl Secure URL to the full exception snapshot, or null
     */
    public function send(string $title, Throwable $exception, array $context, ?string $snapshotUrl = null): void;
}

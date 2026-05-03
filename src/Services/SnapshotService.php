<?php

namespace NightshiftFoundry\AlertStream\Services;

use NightshiftFoundry\AlertStream\Models\Snapshot;
use Throwable;

class SnapshotService
{
    /**
     * Capture an exception snapshot and return its public URL.
     *
     * Returns null when the snapshots feature is disabled.
     */
    public function capture(string $title, Throwable $exception, array $context = []): ?string
    {
        if (! config('alertstream.snapshots.enabled', false)) {
            return null;
        }

        try {
            $fingerprint = md5(get_class($exception) . '|' . $exception->getFile() . '|' . $exception->getLine());

            // Check for existing snapshot with same fingerprint within the dedup window
            $dedupMinutes = config('alertstream.snapshots.dedup_minutes', 0);
            if ($dedupMinutes > 0) {
                $existing = Snapshot::where('fingerprint', $fingerprint)
                    ->where('created_at', '>=', now()->subMinutes($dedupMinutes))
                    ->first();

                if ($existing) {
                    $existing->increment('occurrences');
                    $existing->update(['last_seen_at' => now()]);

                    return $existing->url;
                }
            }

            $hash = hash('sha256', implode('|', [
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                microtime(true),
                random_bytes(8),
            ]));

            $snapshot = Snapshot::create([
                'hash' => $hash,
                'title' => $title,
                'exception_class' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'context' => $context,
                'fingerprint' => $fingerprint,
                'occurrences' => 1,
                'last_seen_at' => now(),
            ]);

            return $snapshot->url;
        } catch (Throwable) {
            // Snapshots must never crash the application
            return null;
        }
    }
}

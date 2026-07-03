<?php

namespace NightshiftFoundry\AlertStream\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

class ThrottleService
{
    /**
     * Determine if this exception should be allowed through.
     *
     * Returns true if the alert should be SENT, false if throttled.
     *
     * Fixed-window cooldown: the first occurrence of a fingerprint (class +
     * file + line) opens a window lasting `cooldown_minutes` and is always
     * allowed. Up to `max` occurrences total are allowed inside that same
     * window; once the cap is reached, every further occurrence is
     * suppressed until the window elapses, however far apart the
     * occurrences are — a bug recurring every few minutes is capped just as
     * reliably as one firing in a tight burst. The window's expiry is set
     * once, at the first hit, and never extended by later hits, so a
     * steady trickle of occurrences can't keep the window open forever.
     */
    public function allow(Throwable $exception): bool
    {
        if (! config('alertstream.throttle.enabled', false)) {
            return true;
        }

        $key = 'alertstream:throttle:' . $this->fingerprint($exception);
        $max = config('alertstream.throttle.max', 5);
        $cooldownMinutes = config('alertstream.throttle.cooldown_minutes', 60);

        $hits = Cache::get($key);

        if ($hits === null) {
            Cache::put($key, 1, now()->addMinutes($cooldownMinutes));

            return $max > 0;
        }

        if ($hits >= $max) {
            return false;
        }

        Cache::increment($key);

        return true;
    }

    /**
     * Generate a fingerprint for this exception type + location.
     */
    public function fingerprint(Throwable $exception): string
    {
        return md5(get_class($exception) . '|' . $exception->getFile() . '|' . $exception->getLine());
    }
}

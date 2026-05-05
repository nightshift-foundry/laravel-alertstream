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
     */
    public function allow(Throwable $exception): bool
    {
        if (! config('alertstream.throttle.enabled', false)) {
            return true;
        }

        $key = 'alertstream:throttle:' . $this->fingerprint($exception);
        $maxPerMinute = config('alertstream.throttle.max_per_minute', 5);

        $hits = Cache::get($key, 0);

        if ($hits >= $maxPerMinute) {
            return false;
        }

        Cache::put($key, $hits + 1, now()->addMinutes(1));

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

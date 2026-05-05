<?php

namespace NightshiftFoundry\AlertStream\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void report(string $message, ?\Throwable $exception = null, array $context = [])
 * @method static void log(string $level, string $message, $data = null, array $context = [])
 * @method static void debug(string $message, $data = null, array $context = [])
 * @method static mixed getConfig(string $key, $default = null)
 *
 * @see \NightshiftFoundry\AlertStream\Services\AlertStreamService
 */
class AlertStream extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'alertstream';
    }
}

<?php

namespace NightshiftFoundry\AlertStream\Enums;

/**
 * Log levels supported by AlertStream.
 *
 * Backed by the lowercase PSR-3 level strings so a case can be passed
 * directly to AlertStream::log() in place of the raw string, e.g.
 * AlertStream::log(AlertStreamLogLevel::ERROR, 'Boom').
 */
enum AlertStreamLogLevel: string
{
    case EMERGENCY = 'emergency';
    case ALERT = 'alert';
    case CRITICAL = 'critical';
    case ERROR = 'error';
    case WARNING = 'warning';
    case NOTICE = 'notice';
    case INFO = 'info';
    case DEBUG = 'debug';
}

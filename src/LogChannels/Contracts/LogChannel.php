<?php

namespace NightshiftFoundry\AlertStream\LogChannels\Contracts;

use Monolog\Logger;

interface LogChannel
{
    public function __invoke(array $config): Logger;
}

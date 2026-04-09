<?php

namespace App\Logging;

use Illuminate\Log\Logger;

class CustomizeLogger
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(new \App\Logging\TraceProcessor);
            $handler->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor);
        }
    }
}

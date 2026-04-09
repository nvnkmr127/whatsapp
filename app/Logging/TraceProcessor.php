<?php

namespace App\Logging;

use App\Services\TraceContext;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class TraceProcessor implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $traceId = TraceContext::getTraceId();

        if ($traceId) {
            $record->extra['trace_id'] = $traceId;
        }

        // Add parent_id if available to see spans
        $parentId = TraceContext::getParentId();
        if ($parentId) {
            $record->extra['parent_id'] = $parentId;
        }

        return $record;
    }
}

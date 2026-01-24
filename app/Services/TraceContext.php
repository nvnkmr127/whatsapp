<?php

namespace App\Services;

class TraceContext
{
    protected static ?string $traceId = null;
    protected static ?string $parentId = null; // The span ID of the caller

    /**
     * Set the current trace context.
     */
    public static function set(string $traceId, ?string $parentId = null): void
    {
        static::$traceId = $traceId;
        static::$parentId = $parentId;
    }

    /**
     * Get the current trace ID.
     */
    public static function getTraceId(): ?string
    {
        return static::$traceId;
    }

    /**
     * Get the current parent ID (for the next event).
     */
    public static function getParentId(): ?string
    {
        return static::$parentId;
    }

    /**
     * Clear the context (e.g., after job or request).
     */
    public static function clear(): void
    {
        static::$traceId = null;
        static::$parentId = null;
    }

    /**
     * Start a new trace if one doesn't exist.
     * Returns the trace ID.
     */
    public static function ensureTraceId(): string
    {
        if (!static::$traceId) {
            static::$traceId = (string) \Illuminate\Support\Str::uuid();
        }
        return static::$traceId;
    }
}

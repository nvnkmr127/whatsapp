<?php

namespace App\Exceptions;

use Exception;

/**
 * Base exception for call-related errors
 */
class CallException extends Exception
{
    protected $errorCode;
    protected $context = [];

    public function __construct(string $message = "", int $errorCode = 0, array $context = [], \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'error' => true,
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'context' => $this->context,
        ];
    }
}

/**
 * Exception for SDP validation errors
 */
class SDPValidationException extends CallException
{
    const ERROR_CODE = 1001;

    public function __construct(string $message, array $validationErrors = [], \Throwable $previous = null)
    {
        parent::__construct(
            $message,
            self::ERROR_CODE,
            ['validation_errors' => $validationErrors],
            $previous
        );
    }
}

/**
 * Exception for call timeout errors
 */
class CallTimeoutException extends CallException
{
    const ERROR_CODE = 1002;

    public function __construct(string $callId, int $timeoutSeconds, \Throwable $previous = null)
    {
        parent::__construct(
            "Call operation timed out after {$timeoutSeconds} seconds",
            self::ERROR_CODE,
            ['call_id' => $callId, 'timeout_seconds' => $timeoutSeconds],
            $previous
        );
    }
}

/**
 * Exception for WebRTC connection errors
 */
class WebRTCConnectionException extends CallException
{
    const ERROR_CODE = 1003;

    public function __construct(string $message, string $connectionState = '', \Throwable $previous = null)
    {
        parent::__construct(
            $message,
            self::ERROR_CODE,
            ['connection_state' => $connectionState],
            $previous
        );
    }
}

/**
 * Exception for invalid call state
 */
class InvalidCallStateException extends CallException
{
    const ERROR_CODE = 1004;

    public function __construct(string $currentState, string $expectedState, \Throwable $previous = null)
    {
        parent::__construct(
            "Invalid call state. Current: {$currentState}, Expected: {$expectedState}",
            self::ERROR_CODE,
            ['current_state' => $currentState, 'expected_state' => $expectedState],
            $previous
        );
    }
}

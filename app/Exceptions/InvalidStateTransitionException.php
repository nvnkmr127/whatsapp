<?php

namespace App\Exceptions;

use Exception;

class InvalidStateTransitionException extends Exception
{
    public function __construct(string $from, string $to, string $message = '')
    {
        $defaultMessage = "Invalid state transition from {$from} to {$to}";
        $fullMessage = $message ? "{$defaultMessage}: {$message}" : $defaultMessage;

        parent::__construct($fullMessage);
    }
}

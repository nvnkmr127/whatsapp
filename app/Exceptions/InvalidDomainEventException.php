<?php

namespace App\Exceptions;

use Exception;

class InvalidDomainEventException extends Exception
{
    protected $validationErrors;

    public function __construct($message, $errors = [])
    {
        parent::__construct($message);
        $this->validationErrors = $errors;
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }
}

<?php

namespace App\Exceptions;

use Exception;

class InvalidOrderStatusTransitionException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = "Invalid order status transition", int $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

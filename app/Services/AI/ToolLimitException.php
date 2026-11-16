<?php

namespace App\Services\AI;

use RuntimeException;

/** Raised when a tool is disabled, not in the user's plan, or the daily limit is reached. */
class ToolLimitException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 403)
    {
        parent::__construct($message);
    }
}

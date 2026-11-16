<?php

namespace App\Services\Credit;

use RuntimeException;

class InsufficientCreditsException extends RuntimeException
{
    public function __construct(public readonly int $required = 0, public readonly int $available = 0)
    {
        parent::__construct('You do not have enough credits for this action.');
    }
}

<?php

namespace App\Services\AI;

use RuntimeException;

/** Provider failure with a message that is safe to show to end users. */
class AIException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $userMessage, public readonly int $status = 502)
    {
        parent::__construct($userMessage);
    }

    public static function notConfigured(): self
    {
        return new self('not_configured', 'The AI service is not configured yet. Please contact the administrator.', 503);
    }

    public static function timeout(): self
    {
        return new self('timeout', 'The AI service took too long to respond. Please try again.', 504);
    }

    public static function rateLimited(): self
    {
        return new self('rate_limited', 'The AI service is busy right now. Please try again in a moment.', 429);
    }

    public static function providerError(): self
    {
        return new self('provider_error', 'The AI service could not complete your request. You were not charged.', 502);
    }

    public static function invalidResponse(): self
    {
        return new self('invalid_response', 'The AI returned an unusable response. Please try again. You were not charged.', 502);
    }
}

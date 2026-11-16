<?php

namespace App\Services\AI;

final class AIRequest
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function __construct(
        public readonly array $messages,
        public readonly bool $expectJson = false,
        public readonly ?int $maxTokens = null,
        public readonly ?float $temperature = null,
    ) {}
}

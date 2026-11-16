<?php

namespace App\Services\AI;

final class AIResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $provider,
        public readonly ?string $model = null,
        public readonly ?int $promptTokens = null,
        public readonly ?int $completionTokens = null,
    ) {}
}

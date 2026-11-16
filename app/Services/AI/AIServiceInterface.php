<?php

namespace App\Services\AI;

interface AIServiceInterface
{
    public function name(): string;

    /** True when credentials needed to call the provider are present. */
    public function isConfigured(): bool;

    /** @throws AIException */
    public function generate(AIRequest $request): AIResponse;
}

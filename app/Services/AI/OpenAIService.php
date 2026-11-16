<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService implements AIServiceInterface
{
    /** @param array{key?: ?string, model: string, base_url: string, timeout: int} $config */
    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return filled($this->config['key'] ?? null);
    }

    public function generate(AIRequest $request): AIResponse
    {
        if (! $this->isConfigured()) {
            throw AIException::notConfigured();
        }

        $payload = [
            'model' => $this->config['model'],
            'messages' => $request->messages,
            'max_completion_tokens' => min($request->maxTokens ?? 1500, (int) config('ai.limits.max_output_tokens')),
        ];
        // Some newer models only accept the default temperature; leave it unset (null) to use the default.
        if ($request->temperature !== null) {
            $payload['temperature'] = $request->temperature;
        }
        if ($request->expectJson) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::withToken($this->config['key'])
                ->withOptions(['version' => 1.1])   // HTTP/1.1: avoids "cURL error 52: Empty reply" seen with some PHP/cURL builds on Windows
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout($this->config['timeout'])
                ->post(rtrim($this->config['base_url'], '/').'/chat/completions', $payload);
        } catch (ConnectionException $e) {
            Log::warning('AI provider connection failed', ['provider' => 'openai', 'reason' => class_basename($e)]);
            throw AIException::timeout();
        }

        if ($response->failed()) {
            // Log only the error type/code. Provider messages can echo parts of the API key.
            Log::warning('AI provider error', [
                'provider' => 'openai',
                'status' => $response->status(),
                'type' => $response->json('error.type'),
                'code' => $response->json('error.code'),
                // Only while APP_DEBUG=true (local development); never enable this in production.
                'detail' => config('app.debug') ? mb_substr((string) $response->body(), 0, 300) : null,
            ]);

            throw $response->status() === 429 ? AIException::rateLimited() : AIException::providerError();
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            Log::warning('AI provider returned empty content', ['provider' => 'openai']);
            throw AIException::invalidResponse();
        }

        return new AIResponse(
            content: trim($content),
            provider: 'openai',
            model: $response->json('model') ?? $this->config['model'],
            promptTokens: $response->json('usage.prompt_tokens'),
            completionTokens: $response->json('usage.completion_tokens'),
        );
    }
}
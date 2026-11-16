<?php

namespace App\Services\AI;

use App\Models\Setting;
use InvalidArgumentException;

/**
 * Resolves the active AI provider. To add a provider: implement AIServiceInterface,
 * add its config under config/ai.php "providers", and map its driver name below.
 */
class AIManager
{
    public function providerName(): string
    {
        $name = Setting::get('ai.provider', config('ai.default'));

        return array_key_exists($name, config('ai.providers')) ? $name : config('ai.default');
    }

    public function driver(?string $name = null): AIServiceInterface
    {
        $name ??= $this->providerName();
        $config = config("ai.providers.$name");

        if (! $config) {
            throw new InvalidArgumentException("Unknown AI provider [$name].");
        }

        // Admin-adjustable, non-secret overrides.
        $config['model'] = Setting::get('ai.model', $config['model']);
        $config['timeout'] = (int) Setting::get('ai.timeout', $config['timeout']);

        return match ($config['driver']) {
            'openai' => new OpenAIService($config),
            default => throw new InvalidArgumentException("Unsupported AI driver [{$config['driver']}]."),
        };
    }
}

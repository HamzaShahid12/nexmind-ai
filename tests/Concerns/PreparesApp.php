<?php

namespace Tests\Concerns;

use App\Models\Plan;
use App\Models\User;
use Database\Seeders\AiToolSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Http;

trait PreparesApp
{
    protected function seedBase(): void
    {
        $this->seed([RolePermissionSeeder::class, PlanSeeder::class, AiToolSeeder::class]);
    }

    protected function makeUser(string $role = 'user', int $credits = 50, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['plan_id' => Plan::default()->id, 'credits_balance' => $credits, 'status' => 'active'])->save();
        $user->assignRole($role);

        return $user->refresh();   // load DB defaults so the model matches a real stored user
    }

    /** Configure a fake OpenAI key and a successful provider response. */
    protected function fakeAi(string|array $content, int $status = 200): void
    {
        config(['ai.providers.openai.key' => 'sk-test-key-1234']);

        Http::fake(['api.openai.com/*' => Http::response(
            $status === 200
                ? ['model' => 'gpt-test', 'choices' => [['message' => ['content' => is_array($content) ? json_encode($content) : $content]]], 'usage' => ['prompt_tokens' => 11, 'completion_tokens' => 22]]
                : ['error' => ['type' => 'server_error', 'message' => 'internal secret detail']],
            $status,
        )]);
    }

    protected function emailPayload(array $overrides = []): array
    {
        return $overrides + [
            'purpose' => 'Introduce our service', 'recipient_type' => 'Prospective client',
            'key_information' => 'We offer bookkeeping for small shops.', 'tone' => 'professional', 'length' => 'short',
        ];
    }

    protected function emailResult(): array
    {
        return ['subject' => 'Hello', 'greeting' => 'Hi', 'body' => 'Body text', 'closing' => 'Regards', 'follow_up' => ['subject' => 'Following up', 'body' => 'Checking in']];
    }
}

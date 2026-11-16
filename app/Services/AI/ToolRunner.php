<?php

namespace App\Services\AI;

use App\Models\AiGeneration;
use App\Models\AiTool;
use App\Models\Conversation;
use App\Models\UploadedDocument;
use App\Models\UsageLog;
use App\Models\User;
use App\Services\Credit\CreditService;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Single entry point for every AI tool. Order of operations:
 *   1. tool is active / in plan / under daily limit / user can afford it
 *   2. call the provider (no credits touched yet)
 *   3. validate the response
 *   4. in ONE transaction: store the generation, charge credits, write usage log
 * A failure at any step before 4 costs the user nothing.
 */
class ToolRunner
{
    public function __construct(
        private readonly AIManager $ai,
        private readonly PromptTemplateService $prompts,
        private readonly CreditService $credits,
    ) {}

    public function run(User $user, string $slug, array $input): AiGeneration
    {
        $tool = $this->tool($slug);
        $request = $this->prompts->build($tool, $input);

        return $this->execute($user, $tool, $request, $input, $this->prompts->titleFor($slug, $input));
    }

    public function summarizeDocument(User $user, UploadedDocument $doc): AiGeneration
    {
        $tool = $this->tool('document-summarizer');
        $request = $this->prompts->build($tool, [], ['document_text' => $doc->extracted_text]);

        return $this->execute($user, $tool, $request, ['document_id' => $doc->id, 'filename' => $doc->original_name],
            'Summary: '.Str::limit($doc->original_name, 100));
    }

    public function askDocument(User $user, UploadedDocument $doc, string $question): AiGeneration
    {
        $tool = $this->tool('document-summarizer');
        $request = $this->prompts->buildDocumentQuestion($tool, (string) $doc->extracted_text, $question);

        return $this->execute($user, $tool, $request, ['document_id' => $doc->id, 'filename' => $doc->original_name, 'question' => $question],
            'Q&A: '.Str::limit($question, 100), asText: true);
    }

    /** @return array{0: Conversation, 1: AiGeneration} */
    public function chat(User $user, ?Conversation $conversation, string $message): array
    {
        $tool = $this->tool('ai-chat');

        $history = $conversation
            ? $conversation->messages()->latest('id')->limit((int) config('ai.limits.chat_history_messages'))->get()->reverse()
                ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->values()->all()
            : [];
        $history[] = ['role' => 'user', 'content' => $message];

        $request = $this->prompts->buildChat($tool, $history);
        $holder = $conversation;

        $generation = $this->execute(
            $user, $tool, $request, ['message' => Str::limit($message, 2000)], 'Chat', asText: true,
            beforeCharge: function (AiGeneration $gen) use ($user, &$holder, $message) {
                $holder ??= $user->conversations()->create(['title' => Str::limit($message, 60, '…')]);
                $holder->messages()->create(['role' => 'user', 'content' => $message]);
                $holder->messages()->create(['role' => 'assistant', 'content' => $gen->output, 'tokens' => $gen->completion_tokens]);
                $holder->update(['last_message_at' => now()]);
                $gen->update(['conversation_id' => $holder->id, 'title' => $holder->title]);
            },
        );

        return [$holder, $generation];
    }

    // ----------------------------------------------------------------------------

    private function tool(string $slug): AiTool
    {
        return AiTool::query()->where('slug', $slug)->firstOrFail();
    }

    private function guard(User $user, AiTool $tool): void
    {
        if (! $tool->is_active) {
            throw new ToolLimitException('This tool is currently unavailable.', 403);
        }
        if ($user->plan && ! $user->plan->includesTool($tool)) {
            throw new ToolLimitException('This tool is not included in your plan.', 403);
        }
        if ($tool->daily_limit !== null) {
            $today = AiGeneration::query()->where('user_id', $user->id)->where('ai_tool_id', $tool->id)->where('created_at', '>=', now()->startOfDay())->count();
            if ($today >= $tool->daily_limit) {
                throw new ToolLimitException('You have reached the daily limit for this tool. Please try again tomorrow.', 429);
            }
        }
        $this->credits->assertCanAfford($user, $tool->credit_cost);
    }

    private function execute(User $user, AiTool $tool, AIRequest $request, array $input, string $title, bool $asText = false, ?Closure $beforeCharge = null): AiGeneration
    {
        $this->guard($user, $tool);

        $driver = $this->ai->driver();
        $started = microtime(true);

        try {
            $response = $driver->generate($request);
            $isJson = $request->expectJson && ! $asText;
            $output = $isJson ? $this->validatedJson($tool->slug, $response->content) : $response->content;
        } catch (AIException $e) {
            $this->log($user, $tool, $driver->name(), 'error', $started, errorCode: $e->errorCode);
            throw $e;
        }

        return DB::transaction(function () use ($user, $tool, $driver, $response, $output, $isJson, $input, $title, $started, $beforeCharge) {
            $generation = AiGeneration::create([
                'user_id' => $user->id,
                'ai_tool_id' => $tool->id,
                'title' => $title,
                'input' => $input,
                'output' => $output,
                'output_format' => $isJson ? 'json' : 'text',
                'credits_used' => $tool->credit_cost,
                'model' => $response->model,
                'prompt_tokens' => $response->promptTokens,
                'completion_tokens' => $response->completionTokens,
            ]);

            $beforeCharge?->__invoke($generation);

            // Locks the user row; throws (and rolls everything back) if a concurrent request spent the credits first.
            $this->credits->charge($user, $tool->credit_cost, "Used {$tool->name}", $generation);
            $this->log($user, $tool, $driver->name(), 'success', $started, $response, credits: $tool->credit_cost);

            return $generation->load('tool');
        });
    }

    /** Ensure the model returned a JSON object containing every required key. */
    private function validatedJson(string $slug, string $content): string
    {
        $content = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content);
        $data = json_decode($content, true);

        if (! is_array($data)) {
            Log::warning('AI returned non-JSON content', ['tool' => $slug]);
            throw AIException::invalidResponse();
        }
        foreach ($this->prompts->requiredKeys($slug) as $key) {
            if (! array_key_exists($key, $data)) {
                Log::warning('AI JSON missing key', ['tool' => $slug, 'key' => $key]);
                throw AIException::invalidResponse();
            }
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function log(User $user, AiTool $tool, string $provider, string $status, float $started, ?AIResponse $response = null, ?string $errorCode = null, int $credits = 0): void
    {
        try {
            UsageLog::create([
                'user_id' => $user->id,
                'ai_tool_id' => $tool->id,
                'provider' => $provider,
                'model' => $response?->model,
                'prompt_tokens' => $response?->promptTokens,
                'completion_tokens' => $response?->completionTokens,
                'status' => $status,
                'error_code' => $errorCode,
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                'credits_charged' => $credits,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to write usage log', ['reason' => class_basename($e)]);
        }
    }
}

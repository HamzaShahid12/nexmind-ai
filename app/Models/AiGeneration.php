<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiGeneration extends Model
{
    protected $fillable = ['user_id', 'ai_tool_id', 'conversation_id', 'title', 'input', 'output', 'output_format', 'credits_used', 'model', 'prompt_tokens', 'completion_tokens'];

    protected function casts(): array
    {
        return ['input' => 'array', 'credits_used' => 'integer'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function tool(): BelongsTo { return $this->belongsTo(AiTool::class, 'ai_tool_id'); }
    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }

    /** Decoded output for JSON tools, raw text otherwise. */
    public function decodedOutput(): mixed
    {
        return $this->output_format === 'json' ? json_decode($this->output, true) : $this->output;
    }

    /** Plain-text version used for previews, saving and TXT download. */
    public function plainText(): string
    {
        if ($this->output_format !== 'json') {
            return $this->output;
        }

        return $this->flatten($this->decodedOutput() ?? []);
    }

    public function preview(int $limit = 160): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', $this->plainText()), $limit);
    }

    private function flatten(mixed $value, string $prefix = ''): string
    {
        if (! is_array($value)) {
            return (string) $value;
        }
        $lines = [];
        foreach ($value as $key => $item) {
            $label = is_string($key) ? str_replace('_', ' ', ucfirst($key)).': ' : '';
            $lines[] = is_array($item)
                ? trim($label).PHP_EOL.$this->flatten($item)
                : $label.$item;
        }

        return implode(PHP_EOL, $lines);
    }
}

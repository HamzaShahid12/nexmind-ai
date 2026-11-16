<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLog extends Model
{
    protected $fillable = ['user_id', 'ai_tool_id', 'provider', 'model', 'prompt_tokens', 'completion_tokens', 'status', 'error_code', 'duration_ms', 'credits_charged'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function tool(): BelongsTo { return $this->belongsTo(AiTool::class, 'ai_tool_id'); }
}

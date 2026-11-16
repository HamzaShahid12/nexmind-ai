<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedContent extends Model
{
    protected $fillable = ['user_id', 'ai_generation_id', 'title', 'content', 'category'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function generation(): BelongsTo { return $this->belongsTo(AiGeneration::class, 'ai_generation_id'); }
}

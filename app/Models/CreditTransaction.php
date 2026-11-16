<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    protected $fillable = ['user_id', 'type', 'amount', 'balance_after', 'description', 'ai_generation_id', 'admin_id'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'balance_after' => 'integer'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function admin(): BelongsTo { return $this->belongsTo(User::class, 'admin_id'); }
    public function generation(): BelongsTo { return $this->belongsTo(AiGeneration::class, 'ai_generation_id'); }
}

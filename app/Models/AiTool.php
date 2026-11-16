<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiTool extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'category', 'credit_cost', 'is_active', 'daily_limit', 'max_output_tokens', 'temperature', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'credit_cost' => 'integer', 'daily_limit' => 'integer', 'max_output_tokens' => 'integer', 'temperature' => 'float'];
    }

    public function generations(): HasMany { return $this->hasMany(AiGeneration::class); }
}

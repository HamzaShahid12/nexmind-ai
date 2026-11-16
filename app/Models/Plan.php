<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'monthly_credits', 'price', 'is_active', 'is_default', 'sort_order', 'features'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_default' => 'boolean', 'features' => 'array', 'monthly_credits' => 'integer', 'price' => 'decimal:2'];
    }

    public function tools(): BelongsToMany { return $this->belongsToMany(AiTool::class, 'plan_ai_tool'); }
    public function users(): HasMany { return $this->hasMany(User::class); }

    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first() ?? static::query()->where('slug', 'free')->first();
    }

    /** A plan with no explicit tool rows includes every tool. */
    public function includesTool(AiTool $tool): bool
    {
        return ! $this->tools()->exists() || $this->tools()->whereKey($tool->id)->exists();
    }
}

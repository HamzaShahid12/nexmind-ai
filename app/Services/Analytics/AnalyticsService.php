<?php

namespace App\Services\Analytics;

use App\Models\ActivityLog;
use App\Models\AiGeneration;
use App\Models\CreditTransaction;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** All dashboard numbers come from real queries. Nothing is hardcoded or fabricated. */
class AnalyticsService
{
    public function userOverview(User $user): array
    {
        $gens = AiGeneration::query()->where('user_id', $user->id);

        return [
            'plan' => $user->plan ? ['name' => $user->plan->name, 'slug' => $user->plan->slug, 'monthly_credits' => $user->plan->monthly_credits] : null,
            'credits_balance' => $user->credits_balance,
            'credits_reset_at' => $user->credits_reset_at,
            'total_generations' => (clone $gens)->count(),
            'monthly_generations' => (clone $gens)->where('created_at', '>=', now()->startOfMonth())->count(),
            'monthly_credits_used' => (int) (clone $gens)->where('created_at', '>=', now()->startOfMonth())->sum('credits_used'),
            'recent_activity' => (clone $gens)->with('tool:id,name,slug')->latest()->limit(6)->get()
                ->map(fn ($g) => ['id' => $g->id, 'title' => $g->title, 'tool' => $g->tool?->name, 'tool_slug' => $g->tool?->slug, 'credits_used' => $g->credits_used, 'created_at' => $g->created_at])->all(),
            'top_tools' => $this->topTools($user),
        ];
    }

    public function topTools(?User $user = null, int $limit = 5): array
    {
        return AiGeneration::query()
            ->when($user, fn (Builder $q) => $q->where('ai_generations.user_id', $user->id))
            ->join('ai_tools', 'ai_tools.id', '=', 'ai_generations.ai_tool_id')
            ->select('ai_tools.name', 'ai_tools.slug', DB::raw('COUNT(*) as total'), DB::raw('SUM(ai_generations.credits_used) as credits'))
            ->groupBy('ai_tools.id', 'ai_tools.name', 'ai_tools.slug')
            ->orderByDesc('total')->limit($limit)->get()
            ->map(fn ($r) => ['name' => $r->name, 'slug' => $r->slug, 'total' => (int) $r->total, 'credits' => (int) $r->credits])->all();
    }

    /** Generations per day for the last $days days (zero-filled). */
    public function dailyUsage(?User $user = null, int $days = 30): array
    {
        $days = max(1, min($days, 365));
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = AiGeneration::query()
            ->when($user, fn (Builder $q) => $q->where('user_id', $user->id))
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total, SUM(credits_used) as credits')
            ->groupBy('day')->get()->keyBy('day');

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $out[] = ['date' => $day, 'generations' => (int) ($rows[$day]->total ?? 0), 'credits' => (int) ($rows[$day]->credits ?? 0)];
        }

        return $out;
    }

    public function adminOverview(): array
    {
        $recentActive = AiGeneration::query()->where('created_at', '>=', now()->subDays(30))->distinct()->count('user_id');

        return [
            'total_users' => User::query()->count(),
            // "Active" = users who ran at least one generation in the last 30 days.
            'active_users' => $recentActive,
            'total_generations' => AiGeneration::query()->count(),
            'today_generations' => AiGeneration::query()->where('created_at', '>=', now()->startOfDay())->count(),
            'monthly_generations' => AiGeneration::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            'total_credits_consumed' => (int) CreditTransaction::query()->where('type', 'usage')->sum(DB::raw('ABS(amount)')),
            'popular_tools' => $this->topTools(null, 5),
            'usage_trend' => $this->dailyUsage(null, 30),
            'recent_registrations' => User::query()->latest()->limit(6)->get(['id', 'name', 'email', 'created_at'])->all(),
            'recent_activities' => ActivityLog::query()->with('user:id,name')->latest()->limit(8)->get()
                ->map(fn ($a) => ['action' => $a->action, 'user' => $a->user?->name, 'created_at' => $a->created_at])->all(),
        ];
    }

    public function adminUsage(): array
    {
        return [
            'requests_total' => UsageLog::query()->count(),
            'requests_failed' => UsageLog::query()->where('status', 'error')->count(),
            'prompt_tokens' => (int) UsageLog::query()->sum('prompt_tokens'),
            'completion_tokens' => (int) UsageLog::query()->sum('completion_tokens'),
            'trend' => $this->dailyUsage(null, 30),
            'by_tool' => $this->topTools(null, 20),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\GenerationDetailResource;
use App\Http\Resources\GenerationResource;
use App\Models\AiGeneration;
use App\Models\CreditTransaction;
use App\Models\UsageLog;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;

/** Read-only admin views: credit balances, transactions, generations, usage. */
class ReportController extends Controller
{
    public function credits(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100']]);

        return User::query()->with('plan:id,name')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.addcslashes($request->search, '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderByDesc('credits_balance')
            ->paginate($request->integer('per_page', 20), ['id', 'name', 'email', 'plan_id', 'credits_balance', 'credits_reset_at']);
    }

    public function transactions(Request $request)
    {
        $request->validate(['user_id' => ['nullable', 'integer'], 'type' => ['nullable', 'string', 'max:30'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);

        return CreditTransaction::query()->with(['user:id,name,email', 'admin:id,name'])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest('id')->paginate($request->integer('per_page', 20));
    }

    public function generations(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100'], 'tool' => ['nullable', 'string', 'max:60'], 'user_id' => ['nullable', 'integer']]);

        $items = AiGeneration::query()->with(['tool:id,name,slug,category', 'user:id,name,email'])
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.addcslashes($request->search, '%_\\').'%'))
            ->when($request->filled('tool'), fn ($q) => $q->whereHas('tool', fn ($t) => $t->where('slug', $request->tool)))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->latest()->paginate($request->integer('per_page', 20));

        return GenerationResource::collection($items);
    }

    public function generation(AiGeneration $generation)
    {
        return new GenerationDetailResource($generation->load(['tool:id,name,slug,category']));
    }

    public function usage(Request $request, AnalyticsService $analytics)
    {
        $logs = UsageLog::query()->with(['user:id,name', 'tool:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('id')->paginate($request->integer('per_page', 20));

        return response()->json(['summary' => $analytics->adminUsage(), 'logs' => $logs]);
    }
}

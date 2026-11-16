<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\AiTool;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('plan');

        return response()->json(['data' => [
            'balance' => $user->credits_balance,
            'plan' => $user->plan?->only(['name', 'slug', 'monthly_credits']),
            'next_renewal_at' => $user->credits_reset_at,
            'tool_costs' => AiTool::query()->where('is_active', true)->orderBy('sort_order')->get(['name', 'slug', 'credit_cost']),
        ]]);
    }

    public function transactions(Request $request)
    {
        $request->validate(['type' => ['nullable', 'string', 'max:30']]);

        return $request->user()->creditTransactions()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest('id')
            ->paginate($request->integer('per_page', 20));
    }
}

<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Models\AiTool;
use Illuminate\Http\Request;

/** Tools available to the signed-in user, with live credit costs. */
class ToolCatalogController extends Controller
{
    public function index(Request $request)
    {
        $plan = $request->user()->plan;

        $tools = AiTool::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->filter(fn ($tool) => ! $plan || $plan->includesTool($tool))
            ->map(fn ($t) => $t->only(['id', 'slug', 'name', 'description', 'category', 'credit_cost']))
            ->values();

        return response()->json(['data' => $tools]);
    }
}

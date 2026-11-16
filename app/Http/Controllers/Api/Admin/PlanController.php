<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlanController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Plan::query()->withCount('users')->with('tools:id,name,slug')->orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $plan = DB::transaction(fn () => $this->save(new Plan(), $data));
        ActivityLogger::log('admin.plan.created', $plan);

        return response()->json(['data' => $plan->load('tools:id,name,slug')], 201);
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validated($request, $plan);
        DB::transaction(fn () => $this->save($plan, $data));
        ActivityLogger::log('admin.plan.updated', $plan, ['fields' => array_keys($data)]);

        return response()->json(['data' => $plan->fresh()->load('tools:id,name,slug')]);
    }

    public function destroy(Plan $plan)
    {
        if ($plan->is_default || $plan->users()->exists()) {
            throw ValidationException::withMessages(['plan' => ['A default plan or a plan with users cannot be deleted. Disable it instead.']]);
        }
        ActivityLogger::log('admin.plan.deleted', $plan);
        $plan->delete();

        return response()->json(['message' => 'Plan deleted.']);
    }

    private function validated(Request $request, ?Plan $plan = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash', 'max:100', Rule::unique('plans', 'slug')->ignore($plan?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'monthly_credits' => ['required', 'integer', 'min:0', 'max:10000000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:200'],
            'tool_ids' => ['nullable', 'array'],
            'tool_ids.*' => ['integer', 'exists:ai_tools,id'],
        ]);
    }

    private function save(Plan $plan, array $data): Plan
    {
        $tools = $data['tool_ids'] ?? null;
        unset($data['tool_ids']);

        $plan->fill($data)->save();

        if (! empty($data['is_default'])) {
            Plan::query()->whereKeyNot($plan->id)->update(['is_default' => false]);   // only one default plan
        }
        if ($tools !== null) {
            $plan->tools()->sync($tools);
        }

        return $plan;
    }
}

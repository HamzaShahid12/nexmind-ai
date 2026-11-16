<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiTool;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    public function index()
    {
        return response()->json(['data' => AiTool::query()->orderBy('sort_order')->get()]);
    }

    public function update(Request $request, AiTool $tool)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'category' => ['sometimes', 'required', 'string', 'max:50'],
            'credit_cost' => ['sometimes', 'required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'daily_limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100000'],
            'max_output_tokens' => ['sometimes', 'required', 'integer', 'min:100', 'max:'.config('ai.limits.max_output_tokens')],
            'temperature' => ['sometimes', 'nullable', 'numeric', 'between:0,2'],
        ]);

        $tool->update($data);
        ActivityLogger::log('admin.tool.updated', $tool, ['fields' => array_keys($data)]);

        return response()->json(['data' => $tool->fresh()]);
    }
}

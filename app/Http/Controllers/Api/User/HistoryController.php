<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\GenerationDetailResource;
use App\Http\Resources\GenerationResource;
use App\Models\AiGeneration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'tool' => ['nullable', 'string', 'max:60'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $items = AiGeneration::query()
            ->where('user_id', $request->user()->id)          // scoped to the owner
            ->with('tool:id,name,slug,category')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$this->escapeLike($request->search).'%')
                ->orWhere('output', 'like', '%'.$this->escapeLike($request->search).'%')))
            ->when($request->filled('tool'), fn ($q) => $q->whereHas('tool', fn ($t) => $t->where('slug', $request->tool)))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return GenerationResource::collection($items);
    }

    public function show(AiGeneration $generation)
    {
        Gate::authorize('view', $generation);

        return new GenerationDetailResource($generation->load('tool:id,name,slug,category'));
    }

    public function destroy(AiGeneration $generation)
    {
        Gate::authorize('delete', $generation);
        $generation->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}

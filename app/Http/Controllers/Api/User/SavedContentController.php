<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\SaveContentRequest;
use App\Models\AiGeneration;
use App\Models\SavedContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SavedContentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:60']]);

        return SavedContent::query()
            ->where('user_id', $request->user()->id)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.addcslashes($request->search, '%_\\').'%')
                ->orWhere('content', 'like', '%'.addcslashes($request->search, '%_\\').'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->latest()
            ->paginate($request->integer('per_page', 15));
    }

    public function store(SaveContentRequest $request)
    {
        $data = $request->validated();

        if (! empty($data['ai_generation_id'])) {
            $generation = AiGeneration::with('tool')->findOrFail($data['ai_generation_id']);
            Gate::authorize('view', $generation);               // you can only save your own generations

            $data['title'] = $data['title'] ?? $generation->title;
            $data['content'] = $generation->plainText();         // taken from the stored output, not the client
            $data['category'] = $data['category'] ?? $generation->tool?->category;
        }

        $saved = $request->user()->savedContents()->create([
            'ai_generation_id' => $data['ai_generation_id'] ?? null,
            'title' => $data['title'],
            'content' => $data['content'],
            'category' => $data['category'] ?? null,
        ]);

        return response()->json(['data' => $saved], 201);
    }

    public function destroy(SavedContent $savedContent)
    {
        Gate::authorize('delete', $savedContent);
        $savedContent->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}

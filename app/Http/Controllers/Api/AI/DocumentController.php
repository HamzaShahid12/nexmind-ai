<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\DocumentAskRequest;
use App\Http\Requests\AI\DocumentUploadRequest;
use App\Http\Resources\GenerationDetailResource;
use App\Models\UploadedDocument;
use App\Services\AI\ToolRunner;
use App\Services\Document\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents, private readonly ToolRunner $runner) {}

    public function index(Request $request)
    {
        return response()->json(['data' => $request->user()->documents()->latest()->limit(50)
            ->get(['id', 'original_name', 'size', 'char_count', 'truncated', 'expires_at', 'created_at'])]);
    }

    public function store(DocumentUploadRequest $request)
    {
        $user = $request->user();
        $document = $this->documents->ingest($user, $request->file('file'));

        try {
            $generation = $this->runner->summarizeDocument($user, $document);
        } catch (Throwable $e) {
            $document->delete();                              // do not keep text for a failed/unpaid run
            throw $e;
        }

        return response()->json([
            'document' => $document->only(['id', 'original_name', 'size', 'char_count', 'truncated', 'expires_at']),
            'generation' => (new GenerationDetailResource($generation))->resolve(),
            'credits_balance' => $user->credits_balance,
        ], 201);
    }

    public function ask(DocumentAskRequest $request, UploadedDocument $document)
    {
        Gate::authorize('view', $document);                  // never another user's document

        $user = $request->user();
        $generation = $this->runner->askDocument($user, $document, $request->validated('question'));

        return response()->json([
            'answer' => $generation->output,
            'credits_balance' => $user->credits_balance,
        ], 201);
    }

    public function destroy(UploadedDocument $document)
    {
        Gate::authorize('delete', $document);
        $document->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}

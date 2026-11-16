<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\ChatRequest;
use App\Models\Conversation;
use App\Services\AI\ToolRunner;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
    public function __construct(private readonly ToolRunner $runner) {}

    public function store(ChatRequest $request)
    {
        $user = $request->user();
        $conversation = null;

        if ($request->filled('conversation_id')) {
            $conversation = Conversation::findOrFail($request->integer('conversation_id'));
            Gate::authorize('update', $conversation);           // only your own conversations
        }

        [$conversation, $generation] = $this->runner->chat($user, $conversation, $request->validated('message'));

        return response()->json([
            'conversation' => ['id' => $conversation->id, 'title' => $conversation->title],
            'message' => ['role' => 'assistant', 'content' => $generation->output],
            'credits_balance' => $user->credits_balance,
        ], 201);
    }
}

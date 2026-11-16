<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['data' => $request->user()->conversations()
            ->orderByDesc('last_message_at')->orderByDesc('id')->limit(100)->get(['id', 'title', 'last_message_at'])]);
    }

    public function show(Conversation $conversation)
    {
        Gate::authorize('view', $conversation);

        return response()->json(['data' => [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'messages' => $conversation->messages()->orderBy('id')->get(['id', 'role', 'content', 'created_at']),
        ]]);
    }

    public function update(Request $request, Conversation $conversation)
    {
        Gate::authorize('update', $conversation);
        $conversation->update($request->validate(['title' => ['required', 'string', 'max:120']]));

        return response()->json(['data' => $conversation->only(['id', 'title'])]);
    }

    public function destroy(Conversation $conversation)
    {
        Gate::authorize('delete', $conversation);
        $conversation->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}

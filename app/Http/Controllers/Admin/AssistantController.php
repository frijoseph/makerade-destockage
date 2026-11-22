<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Setting;
use App\Support\Assistant\Assistant;
use App\Support\Assistant\ChatBot;
use App\Support\Assistant\KnowledgeBase;
use App\Support\Assistant\LlmClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AssistantController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = AiConversation::query()
            ->with('user')
            ->when($request->query('q'), fn ($query, $search) => $query
                ->where('summary', 'like', "%{$search}%")
                ->orWhereHas('messages', fn ($query) => $query->where('content', 'like', "%{$search}%")))
            ->latest('last_message_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.assistant.index', [
            'conversations' => $conversations,
            'stats' => [
                'today' => AiConversation::query()->where('created_at', '>=', now()->startOfDay())->count(),
                'messages' => AiMessage::query()->where('role', 'user')->where('created_at', '>=', now()->startOfDay())->count(),
                'total' => AiConversation::query()->count(),
                'failed' => AiMessage::query()->where('role', 'assistant')->where('created_at', '>=', now()->subDays(7))->where('meta->fallback', true)->count(),
            ],
            'enabled' => (bool) Setting::get('ai_enabled'),
            'usesModel' => Assistant::usesModel(),
            'modelChosen' => Setting::get('ai_mode') === 'llm',
            'limit' => (int) Setting::get('ai_daily_limit'),
        ]);
    }

    public function show(AiConversation $conversation): View
    {
        $conversation->load(['messages', 'user']);

        return view('admin.assistant.show', compact('conversation'));
    }

    public function destroy(AiConversation $conversation): RedirectResponse
    {
        $conversation->delete();

        return redirect()->route('admin.assistant.index')->with('status', 'Conversation supprimée.');
    }

    /**
     * Sends a test question with the current settings and shows the raw outcome (nothing is stored).
     */
    public function test(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:500']]);

        if (! Assistant::usesModel()) {
            $answer = ChatBot::answer($data['message'], [$data['message']], $request->user());

            return response()->json(['ok' => true, 'reply' => $answer['reply'], 'context_chars' => 0, 'products' => count($answer['products']), 'mode' => 'bot']);
        }

        try {
            $knowledge = KnowledgeBase::build([$data['message']], $request->user());
            $reply = LlmClient::complete($knowledge['prompt'], [['role' => 'user', 'content' => $data['message']]]);
        } catch (RuntimeException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'reply' => $reply, 'context_chars' => mb_strlen($knowledge['prompt']), 'products' => count($knowledge['products'])]);
    }

    /**
     * Deletes the conversations older than the retention period (also run by the scheduler).
     */
    public function purge(): RedirectResponse
    {
        $days = (int) Setting::get('ai_retention_days');
        $deleted = $days > 0 ? AiConversation::query()->where('last_message_at', '<', now()->subDays($days))->delete() : 0;

        return back()->with('status', $deleted ? "{$deleted} conversation(s) ancienne(s) supprimée(s)." : 'Aucune conversation à purger.');
    }
}

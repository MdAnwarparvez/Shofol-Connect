<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Show the AI Support Assistant chat interface.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // If a specific conversation_id is requested via query string
        if ($request->filled('conversation_id')) {
            $conversation = Conversation::findOrFail($request->conversation_id);

            // Strict ownership check
            if ($conversation->user_id !== $user->id) {
                abort(403, 'Unauthorized access to this conversation.');
            }

            $conversation->load('messages');
        } else {
            // Retrieve latest active conversation or create a default one
            $conversation = $user->conversations()
                ->with('messages')
                ->latest('updated_at')
                ->first();

            if (!$conversation) {
                $conversation = $user->conversations()->create([
                    'title' => 'Support Assistant',
                ]);
            }
        }

        $messages = $conversation->messages;

        return view('chat.index', compact('conversation', 'messages'));
    }

    /**
     * Show a specific conversation and continue the chat.
     */
    public function show(Request $request, Conversation $conversation): View
    {
        // Strict ownership check
        if ($conversation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $conversation->load('messages');
        $messages = $conversation->messages;

        return view('chat.index', compact('conversation', 'messages'));
    }

    /**
     * Send a question to the AI Assistant and receive a RAG-grounded response.
     */
    public function send(Request $request, AIService $aiService): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();

        // 1. Retrieve conversation with strict ownership check
        $conversation = null;
        if (!empty($validated['conversation_id'])) {
            $conversation = Conversation::findOrFail($validated['conversation_id']);

            if ($conversation->user_id !== $user->id) {
                abort(403, 'Unauthorized access to this conversation.');
            }
        }

        if (!$conversation) {
            $conversation = $user->conversations()->create([
                'title' => Str::limit(trim($validated['message']), 47, '...'),
            ]);
        } else {
            // Update title from the first question if currently a default placeholder
            $hasMessages = $conversation->messages()->exists();
            if (!$hasMessages || in_array($conversation->title, ['Support Assistant', 'New Support Conversation'], true)) {
                $conversation->update([
                    'title' => Str::limit(trim($validated['message']), 47, '...'),
                ]);
            }
        }

        // 2. Store user question in conversation
        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        // 3. Query AIService with RAG retrieval and multi-turn conversation context
        $priorHistory = $conversation->messages()
            ->where('id', '!=', $userMessage->id)
            ->latest('id')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $aiResult = $aiService->ask($validated['message'], $priorHistory);

        // 4. Store AI assistant response
        $assistantMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $aiResult['answer'],
            'sources' => $aiResult['sources'] ?? [],
        ]);

        // Touch timestamp on conversation to update last activity
        $conversation->touch();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $aiResult['success'],
                'user_message' => [
                    'id' => $userMessage->id,
                    'role' => $userMessage->role,
                    'content' => $userMessage->content,
                    'created_at' => $userMessage->created_at->format('h:i A'),
                ],
                'assistant_message' => [
                    'id' => $assistantMessage->id,
                    'role' => $assistantMessage->role,
                    'content' => $assistantMessage->content,
                    'content_html' => Str::markdown($assistantMessage->content, [
                        'html_input' => 'strip',
                        'allow_unsafe_links' => false,
                    ]),
                    'sources' => $assistantMessage->sources ?? [],
                    'fallback' => $aiResult['fallback'] ?? false,
                    'is_policy_fallback' => $aiResult['is_policy_fallback'] ?? false,
                    'error' => $aiResult['error'] ?? null,
                    'created_at' => $assistantMessage->created_at->format('h:i A'),
                ],
                'conversation_id' => $conversation->id,
                'conversation_title' => $conversation->title,
            ]);
        }

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * Start a fresh conversation session.
     */
    public function newConversation(Request $request): RedirectResponse
    {
        $request->user()->conversations()->create([
            'title' => 'New Support Conversation',
        ]);

        return redirect()
            ->route('chat.index')
            ->with('status', 'Started a new conversation session.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminConversationController extends Controller
{
    /**
     * Display a paginated listing of all user AI conversations for administration & auditing.
     */
    public function index(Request $request): View
    {
        $query = Conversation::query()
            ->with(['user', 'latestMessage'])
            ->withCount('messages')
            ->latest('updated_at');

        // Search by user name, user email, or conversation title
        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhereHas('user', function ($userQuery) use ($term) {
                      $userQuery->where('name', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%");
                  });
            });
        }

        // Filter by escalation status (has support tickets)
        if ($request->input('escalated') === 'yes') {
            $query->has('supportTickets');
        } elseif ($request->input('escalated') === 'no') {
            $query->doesntHave('supportTickets');
        }

        $conversations = $query->paginate(15)->withQueryString();

        // Statistics for overview cards
        $stats = [
            'total' => Conversation::count(),
            'escalated' => Conversation::has('supportTickets')->count(),
            'messages_count' => \App\Models\ChatMessage::count(),
        ];

        return view('admin.conversations.index', compact('conversations', 'stats'));
    }

    /**
     * Display a specific conversation's full transcript in secure read-only audit mode.
     */
    public function show(Conversation $conversation): View
    {
        $conversation->load(['user', 'messages', 'supportTickets']);

        return view('admin.conversations.show', compact('conversation'));
    }
}

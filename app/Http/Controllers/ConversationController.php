<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    /**
     * Display a listing of the user's previous conversations.
     */
    public function index(Request $request): View
    {
        $conversations = $request->user()->conversations()
            ->withCount('messages')
            ->with('latestMessage')
            ->latest('updated_at')
            ->paginate(10);

        return view('conversations.index', compact('conversations'));
    }

    /**
     * Delete a conversation belonging to the user.
     */
    public function destroy(Request $request, Conversation $conversation): RedirectResponse
    {
        // Strict ownership check: prevent deleting another user's conversation
        if ($conversation->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action. You can only delete your own conversations.');
        }

        $title = $conversation->title;
        $conversation->delete();

        return redirect()
            ->route('conversations.index')
            ->with('success', "Conversation \"{$title}\" has been deleted.");
    }
}

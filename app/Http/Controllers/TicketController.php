<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    /**
     * Display a listing of the user's support tickets.
     */
    public function index(Request $request): View
    {
        $tickets = $request->user()->supportTickets()
            ->latest()
            ->paginate(10);

        $openCount = $request->user()->supportTickets()->whereIn('status', ['Open', 'In Progress'])->count();
        $resolvedCount = $request->user()->supportTickets()->whereIn('status', ['Resolved', 'Closed'])->count();

        return view('tickets.index', compact('tickets', 'openCount', 'resolvedCount'));
    }

    /**
     * Show the form for creating a new support ticket.
     */
    public function create(Request $request): View
    {
        $categories = SupportTicket::CATEGORIES;
        $priorities = SupportTicket::PRIORITIES;

        $prefillSubject = '';
        $prefillDescription = '';
        $prefillCategory = 'Technical';
        $conversationId = null;

        // If escalated from an AI chat conversation, pre-fill context safely
        if ($request->filled('conversation_id')) {
            $conversation = $request->user()->conversations()
                ->with('messages')
                ->findOrFail($request->conversation_id);

            $conversationId = $conversation->id;
            $prefillSubject = 'Need Support: ' . $conversation->title;

            // Extract conversation context for human support review
            $messages = $conversation->messages;
            if ($messages->isNotEmpty()) {
                $transcript = $messages->map(function ($m) {
                    $sender = $m->role === 'user' ? 'User Question' : 'AI Assistant';
                    return "[{$sender}]:\n{$m->content}";
                })->implode("\n\n---\n\n");

                $prefillDescription = "Escalated from Shofol Connect AI Assistant (Conversation #{$conversation->id}):\n\n{$transcript}\n\n[Additional Details]: ";
            }

            // Keyword heuristics to suggest the best matching department
            $text = mb_strtolower($conversation->title . ' ' . $prefillDescription);
            if (str_contains($text, 'leave') || str_contains($text, 'casual') || str_contains($text, 'annual') || str_contains($text, 'hrm')) {
                $prefillCategory = 'HRM';
            } elseif (str_contains($text, 'salary') || str_contains($text, 'tax') || str_contains($text, 'payroll') || str_contains($text, 'deduction')) {
                $prefillCategory = 'Payroll';
            } elseif (str_contains($text, 'lead') || str_contains($text, 'pipeline') || str_contains($text, 'crm') || str_contains($text, 'deal')) {
                $prefillCategory = 'CRM';
            } elseif (str_contains($text, 'invoice') || str_contains($text, 'accounting') || str_contains($text, 'payment') || str_contains($text, 'net 30')) {
                $prefillCategory = 'Accounting';
            }
        }

        return view('tickets.create', compact(
            'categories',
            'priorities',
            'prefillSubject',
            'prefillDescription',
            'prefillCategory',
            'conversationId'
        ));
    }

    /**
     * Store a newly created support ticket in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', 'string', Rule::in(SupportTicket::CATEGORIES)],
            'priority' => ['required', 'string', Rule::in(SupportTicket::PRIORITIES)],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $conversationId = null;
        if (!empty($validated['conversation_id'])) {
            // Verify ownership of the referenced conversation
            $conversation = $request->user()->conversations()->findOrFail($validated['conversation_id']);
            $conversationId = $conversation->id;
        }

        $ticket = $request->user()->supportTickets()->create([
            'conversation_id' => $conversationId,
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'Open',
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Support ticket #{$ticket->id} was created successfully. Our team will review your request.");
    }

    /**
     * Display the specified support ticket.
     */
    public function show(Request $request, SupportTicket $ticket): View
    {
        // Strict ownership check: prevent viewing another user's ticket
        if ($ticket->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this support ticket.');
        }

        $ticket->load('conversation');

        return view('tickets.show', compact('ticket'));
    }
}

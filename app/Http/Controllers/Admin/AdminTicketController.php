<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminTicketController extends Controller
{
    /**
     * Display a listing of all support tickets with search and filters.
     */
    public function index(Request $request): View
    {
        $query = SupportTicket::query()
            ->with(['user', 'conversation', 'assignedTo'])
            ->forStatus($request->input('status'))
            ->forCategory($request->input('category'))
            ->forPriority($request->input('priority'))
            ->forAssignedTo($request->input('assigned_to'))
            ->search($request->input('q'))
            ->latest();

        $tickets = $query->paginate(10)->withQueryString();

        $categories = SupportTicket::CATEGORIES;
        $priorities = SupportTicket::PRIORITIES;
        $statuses = SupportTicket::STATUSES;
        $admins = User::where('role', 'admin')->orderBy('name')->get();

        // Statistics for admin dashboard metrics
        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::where('status', 'Open')->count(),
            'in_progress' => SupportTicket::where('status', 'In Progress')->count(),
            'resolved' => SupportTicket::whereIn('status', ['Resolved', 'Closed'])->count(),
        ];

        return view('admin.tickets.index', compact(
            'tickets',
            'categories',
            'priorities',
            'statuses',
            'admins',
            'stats'
        ));
    }

    /**
     * Display ticket details and management form for Admin.
     */
    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'conversation.messages', 'assignedTo']);

        $priorities = SupportTicket::PRIORITIES;
        $statuses = SupportTicket::STATUSES;
        $admins = User::where('role', 'admin')->orderBy('name')->get();

        return view('admin.tickets.show', compact('ticket', 'priorities', 'statuses', 'admins'));
    }

    /**
     * Update ticket status, priority, assigned agent, and administrator resolution note.
     */
    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(SupportTicket::STATUSES)],
            'priority' => ['required', 'string', Rule::in(SupportTicket::PRIORITIES)],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'admin')],
            'admin_response' => ['nullable', 'string', 'max:10000'],
        ]);

        // Automatically timestamp when resolving or closing
        if (in_array($validated['status'], ['Resolved', 'Closed'], true) && is_null($ticket->resolved_at)) {
            $validated['resolved_at'] = now();
        } elseif (in_array($validated['status'], ['Open', 'In Progress'], true)) {
            $validated['resolved_at'] = null;
        }

        $ticket->update($validated);

        return redirect()
            ->route('admin.tickets.show', $ticket)
            ->with('success', "Support ticket #{$ticket->id} updated successfully.");
    }
}

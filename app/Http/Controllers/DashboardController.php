<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the authenticated user's personal dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // User personal metrics (isolated to authenticated user)
        $conversationCount = $user->conversations()->count();
        $ticketCount = $user->supportTickets()->count();
        $openTicketCount = $user->supportTickets()->whereIn('status', ['Open', 'In Progress'])->count();
        $resolvedTicketCount = $user->supportTickets()->whereIn('status', ['Resolved', 'Closed'])->count();

        // User recent activity
        $recentConversations = $user->conversations()
            ->withCount('messages')
            ->latest()
            ->take(4)
            ->get();

        $recentTickets = $user->supportTickets()
            ->latest()
            ->take(4)
            ->get();

        return view('dashboard', compact(
            'conversationCount',
            'ticketCount',
            'openTicketCount',
            'resolvedTicketCount',
            'recentConversations',
            'recentTickets'
        ));
    }
}

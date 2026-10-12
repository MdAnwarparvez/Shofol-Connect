<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\KnowledgeArticle;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard with comprehensive system statistics and analytics.
     */
    public function index(): View
    {
        // 1. Registered User metrics
        $totalUsers = User::count();
        $adminUsersCount = User::where('role', 'admin')->count();
        $regularUsersCount = User::where('role', 'user')->count();

        // 2. Knowledge Base metrics
        $kbCount = KnowledgeArticle::count();
        $kbActiveCount = KnowledgeArticle::active()->count();
        $kbInactiveCount = KnowledgeArticle::where('status', 'inactive')->count();

        $rawKbModuleCounts = KnowledgeArticle::selectRaw('module, count(*) as count')
            ->groupBy('module')
            ->pluck('count', 'module')
            ->toArray();

        $articlesByModule = [];
        foreach (KnowledgeArticle::MODULES as $module) {
            $articlesByModule[$module] = (int) ($rawKbModuleCounts[$module] ?? 0);
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('shofol_modules')) {
                $additionalModules = \App\Models\ShofolModule::whereNotIn('code', KnowledgeArticle::MODULES)->ordered()->get();
                foreach ($additionalModules as $mod) {
                    $articlesByModule[$mod->code] = (int) ($rawKbModuleCounts[$mod->code] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback
        }

        // 3. AI Conversations & Chat Messages metrics
        $totalConversations = Conversation::count();
        $totalMessages = ChatMessage::count();
        $userMessagesCount = ChatMessage::where('role', 'user')->count();
        $assistantMessagesCount = ChatMessage::where('role', 'assistant')->count();

        // 4. Support Ticket metrics
        $ticketCount = SupportTicket::count();

        $rawTicketStatusCounts = SupportTicket::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $ticketsByStatus = [];
        foreach (SupportTicket::STATUSES as $status) {
            $ticketsByStatus[$status] = (int) ($rawTicketStatusCounts[$status] ?? 0);
        }

        $ticketOpenCount = ($ticketsByStatus['Open'] ?? 0) + ($ticketsByStatus['In Progress'] ?? 0);
        $ticketResolvedCount = ($ticketsByStatus['Resolved'] ?? 0) + ($ticketsByStatus['Closed'] ?? 0);

        $rawTicketPriorityCounts = SupportTicket::selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $ticketsByPriority = [];
        foreach (SupportTicket::PRIORITIES as $priority) {
            $ticketsByPriority[$priority] = (int) ($rawTicketPriorityCounts[$priority] ?? 0);
        }

        $highPriorityTicketsCount = SupportTicket::where('priority', 'High')
            ->whereIn('status', ['Open', 'In Progress'])
            ->count();

        // 5. Recent Support Tickets (with owner eager-loaded)
        $recentTickets = SupportTicket::with('user')
            ->latest()
            ->take(5)
            ->get();

        // 6. Recent AI Conversations (with owner and messages count eager-loaded)
        $recentConversations = Conversation::with('user')
            ->withCount('messages')
            ->latest()
            ->take(5)
            ->get();

        // 7. Conversation timeline activity (past 14 days)
        $days = 14;
        $startDate = now()->subDays($days - 1)->startOfDay();

        $dailyConversationsRaw = Conversation::selectRaw('DATE(created_at) as date_val, count(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date_val')
            ->pluck('count', 'date_val')
            ->toArray();

        $conversationTimeline = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dateObj = now()->subDays($i);
            $dateKey = $dateObj->format('Y-m-d');
            $label = $dateObj->format('M d');
            $conversationTimeline[] = [
                'date' => $dateKey,
                'label' => $label,
                'count' => (int) ($dailyConversationsRaw[$dateKey] ?? 0),
            ];
        }

        return view('admin.dashboard', compact(
            'totalUsers',
            'adminUsersCount',
            'regularUsersCount',
            'kbCount',
            'kbActiveCount',
            'kbInactiveCount',
            'articlesByModule',
            'totalConversations',
            'totalMessages',
            'userMessagesCount',
            'assistantMessagesCount',
            'ticketCount',
            'ticketOpenCount',
            'ticketResolvedCount',
            'ticketsByStatus',
            'ticketsByPriority',
            'highPriorityTicketsCount',
            'recentTickets',
            'recentConversations',
            'conversationTimeline'
        ));
    }
}
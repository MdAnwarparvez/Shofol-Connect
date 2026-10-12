@extends('layouts.app-bootstrap')

@section('title', 'User Dashboard — Shofol Connect')

@section('content')
@if (Auth::user()->isAdmin())
    <div class="alert alert-warning d-flex align-items-center justify-content-between shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-exclamation fs-4"></i>
            <div>
                <strong>Administrator Account Detected:</strong> You have full administrative privileges to manage knowledge articles and tickets.
            </div>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-dark text-nowrap ms-3">
            <i class="bi bi-shield-lock me-1"></i> Go to Admin Panel
        </a>
    </div>
@endif

<!-- Welcome Jumbotron -->
<div class="p-4 p-md-5 mb-4 rounded-3 bg-white shadow-sm border">
    <div class="container-fluid py-2">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                <i class="bi bi-person-check me-1"></i> User Workspace
            </span>
        </div>
        <h1 class="display-6 fw-bold">Welcome back, {{ Auth::user()->name }}!</h1>
        <p class="col-md-9 fs-5 text-muted mb-0">
            Shofol Connect AI Support Assistant helps you quickly find answers to your HRM, Payroll, CRM, and Accounting questions.
        </p>
    </div>
</div>

<!-- User Personal Activity Metrics (Isolated to Current User) -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">My Conversations</span>
                        <h3 class="fw-bold mb-0 mt-1">{{ $conversationCount }}</h3>
                    </div>
                    <div class="bg-primary-subtle text-primary rounded p-3">
                        <i class="bi bi-chat-left-dots-fill fs-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('conversations.index') }}" class="small text-primary text-decoration-none fw-semibold">
                        View Chat History <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-warning h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">My Support Tickets</span>
                        <h3 class="fw-bold mb-0 mt-1">{{ $ticketCount }}</h3>
                    </div>
                    <div class="bg-warning-subtle text-warning-emphasis rounded p-3">
                        <i class="bi bi-ticket-detailed-fill fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 small text-muted">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1">{{ $openTicketCount }} Open</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $resolvedTicketCount }} Resolved</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Quick Actions</span>
                        <div class="fw-bold text-dark mt-1">Self-Service Portals</div>
                    </div>
                    <div class="bg-success-subtle text-success rounded p-3">
                        <i class="bi bi-lightning-charge-fill fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <a href="{{ route('chat.index') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-robot me-1"></i>Ask AI
                    </a>
                    <a href="{{ route('tickets.create') }}" class="btn btn-sm btn-outline-warning text-dark fw-semibold">
                        <i class="bi bi-plus-circle me-1"></i>New Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Primary Feature Cards -->
<div class="row g-4 mb-4">
    <!-- AI Assistant Portal Card -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="bg-primary-subtle text-primary rounded p-2 lh-1">
                        <i class="bi bi-robot fs-4"></i>
                    </div>
                    <h5 class="card-title fw-bold mb-0">AI Support Assistant</h5>
                </div>
                <p class="card-text text-muted">
                    Ask real-time questions about company policies, leave balance, payroll calculations, and CRM pipelines.
                </p>
                <div class="mt-auto pt-3">
                    <a href="{{ route('chat.index') }}" class="btn btn-primary w-100">
                        <i class="bi bi-chat-left-dots me-1"></i> Launch AI Assistant
                    </a>
                    <a href="{{ route('conversations.index') }}" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                        <i class="bi bi-clock-history me-1"></i> View Chat History
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Knowledge Base Card -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0 border-top border-4 border-success">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="bg-success-subtle text-success rounded p-2 lh-1">
                        <i class="bi bi-book fs-4"></i>
                    </div>
                    <h5 class="card-title fw-bold mb-0">Knowledge Base Search</h5>
                </div>
                <p class="card-text text-muted">
                    Our AI assistant automatically searches company documentation across HRM, Payroll, CRM, and Accounting.
                </p>
                <div class="mt-auto pt-3">
                    <a href="{{ route('chat.index') }}" class="btn btn-outline-success w-100">
                        <i class="bi bi-search me-1"></i> Search via AI Assistant
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Support Tickets Card -->
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0 border-top border-4 border-warning">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="bg-warning-subtle text-warning rounded p-2 lh-1">
                        <i class="bi bi-ticket-perforated fs-4"></i>
                    </div>
                    <h5 class="card-title fw-bold mb-0">Support Tickets</h5>
                </div>
                <p class="card-text text-muted">
                    Need further help? Escalate unanswered questions directly into tickets for human support.
                </p>
                <div class="mt-auto pt-3">
                    <a href="{{ route('tickets.index') }}" class="btn btn-warning w-100 fw-semibold text-dark">
                        <i class="bi bi-ticket-detailed me-1"></i> My Support Tickets
                    </a>
                    <a href="{{ route('tickets.create') }}" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                        <i class="bi bi-plus-circle me-1"></i> Create New Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User's Recent Activity Tables -->
<div class="row g-4">
    <!-- User Recent Conversations -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-clock-history text-primary me-2"></i>Recent Conversations
                </h6>
                <a href="{{ route('conversations.index') }}" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                @if ($recentConversations->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach ($recentConversations as $convo)
                            <a href="{{ route('chat.show', $convo->id) }}" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold text-dark text-truncate" style="max-width: 70%;">
                                        {{ $convo->title }}
                                    </h6>
                                    <small class="text-muted">{{ $convo->updated_at->diffForHumans() }}</small>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-light text-muted border">
                                        <i class="bi bi-chat-text me-1"></i>{{ $convo->messages_count }} {{ Str::plural('message', $convo->messages_count) }}
                                    </span>
                                    <span class="text-primary small fw-semibold">
                                        Continue <i class="bi bi-arrow-right"></i>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-chat-square-dots fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <p class="mb-2">No conversations recorded yet.</p>
                        <a href="{{ route('chat.index') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Start Your First Chat
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- User Recent Support Tickets -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-ticket-detailed text-warning me-2"></i>Recent Support Tickets
                </h6>
                <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-warning text-dark fw-semibold">
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                @if ($recentTickets->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach ($recentTickets as $ticket)
                            <a href="{{ route('tickets.show', $ticket->id) }}" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 65%;">
                                        <span class="text-muted me-1 font-monospace">#{{ $ticket->ticket_number }}</span>
                                        {{ $ticket->subject }}
                                    </div>
                                    <span class="badge {{ $ticket->status_badge_class }}">
                                        {{ $ticket->status }}
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1 small">
                                    <span class="badge {{ $ticket->priority_badge_class }}">
                                        {{ $ticket->priority }} Priority
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        {{ $ticket->category }}
                                    </span>
                                    <span class="text-muted ms-auto">
                                        {{ $ticket->created_at->format('M d, Y') }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-ticket-perforated fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <p class="mb-2">No support tickets submitted yet.</p>
                        <a href="{{ route('tickets.create') }}" class="btn btn-sm btn-warning text-dark fw-semibold">
                            <i class="bi bi-plus-lg me-1"></i>Create a Support Ticket
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

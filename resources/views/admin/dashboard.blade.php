@extends('layouts.app-bootstrap')

@section('title', 'Admin Dashboard & Analytics — Shofol Connect')

@section('content')
<!-- Dashboard Title & Header -->
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1">
            <i class="bi bi-shield-lock-fill text-danger me-2"></i>Admin Dashboard
        </h2>
        <p class="text-muted mb-0">
            System administration, knowledge base controls, AI conversation oversight, and support ticket analytics.
        </p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6">
            <i class="bi bi-person-badge me-1"></i> Administrator Access
        </span>
    </div>
</div>

<!-- High Priority Unresolved Tickets Alert Banner -->
@if ($highPriorityTicketsCount > 0)
    <div class="alert alert-danger d-flex align-items-center justify-content-between shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-octagon-fill fs-4 text-danger"></i>
            <div>
                <strong>Urgent Attention Required:</strong> There {{ $highPriorityTicketsCount === 1 ? 'is' : 'are' }}
                <span class="badge bg-danger text-white fs-6 mx-1">{{ $highPriorityTicketsCount }}</span>
                high-priority unresolved support {{ Str::plural('ticket', $highPriorityTicketsCount) }} awaiting team action.
            </div>
        </div>
        <a href="{{ route('admin.tickets.index', ['priority' => 'High', 'status' => 'Open']) }}" class="btn btn-sm btn-danger text-nowrap ms-3">
            <i class="bi bi-ticket-detailed me-1"></i> View Urgent Tickets
        </a>
    </div>
@endif

<!-- Primary Overview Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Total Registered Users Card -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0 border-start border-4 border-info">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Module 1 • Auth</span>
                    <div class="bg-info-subtle text-info rounded p-2 lh-1">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                </div>
                <h6 class="card-title text-muted fw-normal mb-1">Registered Users</h6>
                <div class="d-flex align-items-baseline gap-2 mb-2">
                    <span class="fs-3 fw-bold text-dark">{{ $totalUsers }}</span>
                    <span class="text-muted small">total accounts</span>
                </div>
                <div class="mt-auto pt-2 border-top d-flex gap-1">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">
                        {{ $regularUsersCount }} Users
                    </span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">
                        {{ $adminUsersCount }} Admins
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Knowledge Base Articles Card -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0 border-start border-4 border-primary">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Module 2 • KB</span>
                    <div class="bg-primary-subtle text-primary rounded p-2 lh-1">
                        <i class="bi bi-journal-bookmark-fill fs-5"></i>
                    </div>
                </div>
                <h6 class="card-title text-muted fw-normal mb-1">Knowledge Articles</h6>
                <div class="d-flex align-items-baseline gap-2 mb-2">
                    <span class="fs-3 fw-bold text-dark">{{ $kbCount }}</span>
                    <span class="text-muted small">total articles</span>
                </div>
                <div class="mt-auto pt-2 border-top d-flex gap-1">
                    <span class="badge bg-success-subtle text-success border border-success-subtle small">
                        {{ $kbActiveCount }} Active
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle small">
                        {{ $kbInactiveCount }} Inactive
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Conversations & Messages Card -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Module 3-5 • AI Chat</span>
                    <div class="bg-success-subtle text-success rounded p-2 lh-1">
                        <i class="bi bi-chat-dots-fill fs-5"></i>
                    </div>
                </div>
                <h6 class="card-title text-muted fw-normal mb-1">AI Conversations</h6>
                <div class="d-flex align-items-baseline gap-2 mb-2">
                    <span class="fs-3 fw-bold text-dark">{{ $totalConversations }}</span>
                    <span class="text-muted small">sessions ({{ $totalMessages }} msgs)</span>
                </div>
                <div class="mt-auto pt-2 border-top d-flex gap-1">
                    <span class="badge bg-info-subtle text-info border border-info-subtle small">
                        {{ $userMessagesCount }} User
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle small">
                        {{ $assistantMessagesCount }} AI Assistant
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Support Tickets Card -->
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Module 6 • Tickets</span>
                    <div class="bg-warning-subtle text-warning rounded p-2 lh-1">
                        <i class="bi bi-ticket-detailed-fill fs-5"></i>
                    </div>
                </div>
                <h6 class="card-title text-muted fw-normal mb-1">Support Tickets</h6>
                <div class="d-flex align-items-baseline gap-2 mb-2">
                    <span class="fs-3 fw-bold text-dark">{{ $ticketCount }}</span>
                    <span class="text-muted small">total inquiries</span>
                </div>
                <div class="mt-auto pt-2 border-top d-flex gap-1">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">
                        {{ $ticketOpenCount }} Open
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle small">
                        {{ $ticketResolvedCount }} Resolved
                    </span>
                    @if ($highPriorityTicketsCount > 0)
                        <span class="badge bg-danger text-white small ms-auto" title="High Priority Unresolved">
                            {{ $highPriorityTicketsCount }} Urgent
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Toolbar -->
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-muted small text-uppercase">
                <i class="bi bi-lightning-charge text-warning me-1"></i>Quick Management:
            </span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.knowledge-articles.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i> New KB Article
            </a>
            <a href="{{ route('admin.knowledge-articles.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-journals me-1"></i> Knowledge Base
            </a>
            <a href="{{ route('chat.index') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-robot me-1"></i> Test AI Assistant
            </a>
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-warning text-dark fw-semibold">
                <i class="bi bi-ticket-perforated me-1"></i> Manage Tickets
            </a>
            <a href="{{ route('admin.tickets.index', ['status' => 'Open']) }}" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-clock-history me-1"></i> Pending Tickets ({{ $ticketOpenCount }})
            </a>
        </div>
    </div>
</div>

<!-- Visual Charts & Analytics Grid (Chart.js) -->
<div class="row g-4 mb-4">
    <!-- Chart 1: Knowledge Base by Module -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-pie-chart-fill text-primary me-2"></i>Articles by Module
                </h6>
                <span class="badge bg-light text-muted border">{{ $kbCount }} total</span>
            </div>
            <div class="card-body d-flex flex-column">
                <div style="position: relative; height: 180px;">
                    <canvas id="chartKbModules"></canvas>
                </div>
                <!-- Accessible Summary Table / Badges -->
                <div class="mt-3 pt-2 border-top small">
                    <div class="row text-center g-1">
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">HRM</span>
                            <span class="fw-bold text-primary">{{ $articlesByModule['HRM'] ?? 0 }}</span>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Payroll</span>
                            <span class="fw-bold text-success">{{ $articlesByModule['Payroll'] ?? 0 }}</span>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">CRM</span>
                            <span class="fw-bold text-warning-emphasis">{{ $articlesByModule['CRM'] ?? 0 }}</span>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Accounting</span>
                            <span class="fw-bold text-info">{{ $articlesByModule['Accounting'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 2: Support Tickets by Status -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-doughnut-chart text-danger me-2"></i>Tickets by Status
                </h6>
                <span class="badge bg-light text-muted border">{{ $ticketCount }} total</span>
            </div>
            <div class="card-body d-flex flex-column">
                <div style="position: relative; height: 180px;">
                    <canvas id="chartTicketStatus"></canvas>
                </div>
                <!-- Accessible Summary Table / Badges -->
                <div class="mt-3 pt-2 border-top small">
                    <div class="row text-center g-1">
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Open</span>
                            <span class="fw-bold text-danger">{{ $ticketsByStatus['Open'] ?? 0 }}</span>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">In Progress</span>
                            <span class="fw-bold text-warning-emphasis">{{ $ticketsByStatus['In Progress'] ?? 0 }}</span>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Resolved</span>
                            <span class="fw-bold text-success">{{ $ticketsByStatus['Resolved'] ?? 0 }}</span>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Closed</span>
                            <span class="fw-bold text-secondary">{{ $ticketsByStatus['Closed'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 3: Support Tickets by Priority -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-bar-chart-fill text-warning me-2"></i>Tickets by Priority
                </h6>
                <span class="badge bg-light text-muted border">Urgency</span>
            </div>
            <div class="card-body d-flex flex-column">
                <div style="position: relative; height: 180px;">
                    <canvas id="chartTicketPriority"></canvas>
                </div>
                <!-- Accessible Summary Table / Badges -->
                <div class="mt-3 pt-2 border-top small">
                    <div class="row text-center g-1">
                        <div class="col-4">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">High</span>
                            <span class="fw-bold text-danger">{{ $ticketsByPriority['High'] ?? 0 }}</span>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Medium</span>
                            <span class="fw-bold text-warning-emphasis">{{ $ticketsByPriority['Medium'] ?? 0 }}</span>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Low</span>
                            <span class="fw-bold text-info">{{ $ticketsByPriority['Low'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 4: AI Conversation Activity Timeline -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-graph-up text-success me-2"></i>14-Day Activity
                </h6>
                <span class="badge bg-light text-muted border">Timeline</span>
            </div>
            <div class="card-body d-flex flex-column">
                <div style="position: relative; height: 180px;">
                    <canvas id="chartTimeline"></canvas>
                </div>
                <!-- Accessible Summary Table / Badges -->
                <div class="mt-3 pt-2 border-top small text-center text-muted">
                    <span>Active Sessions in Period: <strong>{{ array_sum(array_column($conversationTimeline, 'count')) }}</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Records Tables (Recent Tickets & Recent Conversations) -->
<div class="row g-4 mb-4">
    <!-- Recent Support Tickets -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-ticket-perforated-fill text-warning me-2"></i>Recent Support Tickets
                </h6>
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-outline-warning text-dark fw-semibold">
                    View All Tickets
                </a>
            </div>
            <div class="card-body p-0">
                @if ($recentTickets->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Subject & User</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentTickets as $ticket)
                                    <tr>
                                        <td>
                                            <span class="font-monospace fw-semibold text-primary small">
                                                #{{ $ticket->ticket_number }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-truncate text-dark" style="max-width: 200px;">
                                                {{ $ticket->subject }}
                                            </div>
                                            <div class="small text-muted text-truncate" style="max-width: 200px;">
                                                {{ $ticket->user->name ?? 'User #'.$ticket->user_id }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $ticket->priority_badge_class }}">
                                                {{ $ticket->priority }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $ticket->status_badge_class }}">
                                                {{ $ticket->status }}
                                            </span>
                                        </td>
                                        <td class="small text-muted text-nowrap">
                                            {{ $ticket->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary" title="Review Ticket">
                                                <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-ticket-perforated fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <p class="mb-0">No support tickets recorded yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent AI Conversations -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-chat-left-quote-fill text-success me-2"></i>Recent AI Conversations
                </h6>
                <a href="{{ route('conversations.index') }}" class="btn btn-sm btn-outline-success">
                    View Conversations
                </a>
            </div>
            <div class="card-body p-0">
                @if ($recentConversations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Conversation Title</th>
                                    <th>User</th>
                                    <th>Messages</th>
                                    <th>Last Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentConversations as $convo)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-truncate text-dark" style="max-width: 240px;">
                                                {{ $convo->title }}
                                            </div>
                                            <small class="text-muted font-monospace">ID: #{{ $convo->id }}</small>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-dark">
                                                {{ $convo->user->name ?? 'User #'.$convo->user_id }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ $convo->user->email ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-chat-dots me-1"></i>{{ $convo->messages_count }}
                                            </span>
                                        </td>
                                        <td class="small text-muted text-nowrap">
                                            {{ $convo->updated_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-chat-square-dots fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <p class="mb-0">No conversations recorded yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- System Status & Configuration Panel -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-server text-primary me-2"></i>System Status & Configuration
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-6 col-md-3">
                <div class="p-3 bg-light rounded">
                    <div class="text-muted small">Application</div>
                    <div class="fw-bold">Shofol Connect MVP</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="p-3 bg-light rounded">
                    <div class="text-muted small">Framework</div>
                    <div class="fw-bold">Laravel {{ app()->version() }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="p-3 bg-light rounded">
                    <div class="text-muted small">Database</div>
                    <div class="fw-bold">{{ config('database.default') }} ({{ config('database.connections.mysql.database') }})</div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="p-3 bg-light rounded">
                    <div class="text-muted small">Logged-in Administrator</div>
                    <div class="fw-bold text-truncate">{{ Auth::user()->name }} ({{ Auth::user()->email }})</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Chart.js CDN for Responsive Admin Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js library not loaded');
        return;
    }

    // Chart 1: Knowledge Base Articles by Module
    const ctxKb = document.getElementById('chartKbModules');
    if (ctxKb) {
        new Chart(ctxKb, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($articlesByModule)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($articlesByModule)) !!},
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#0dcaf0'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '65%'
            }
        });
    }

    // Chart 2: Support Tickets by Status
    const ctxStatus = document.getElementById('chartTicketStatus');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($ticketsByStatus)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($ticketsByStatus)) !!},
                    backgroundColor: ['#dc3545', '#fd7e14', '#198754', '#6c757d'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '65%'
            }
        });
    }

    // Chart 3: Support Tickets by Priority
    const ctxPriority = document.getElementById('chartTicketPriority');
    if (ctxPriority) {
        new Chart(ctxPriority, {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_keys($ticketsByPriority)) !!},
                datasets: [{
                    label: 'Tickets',
                    data: {!! json_encode(array_values($ticketsByPriority)) !!},
                    backgroundColor: ['#0dcaf0', '#ffc107', '#dc3545'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Chart 4: AI Conversation Timeline Activity
    const ctxTimeline = document.getElementById('chartTimeline');
    if (ctxTimeline) {
        new Chart(ctxTimeline, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_column($conversationTimeline, 'label')) !!},
                datasets: [{
                    label: 'Conversations',
                    data: {!! json_encode(array_column($conversationTimeline, 'count')) !!},
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.12)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 2,
                    pointBackgroundColor: '#198754'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
</script>
@endsection

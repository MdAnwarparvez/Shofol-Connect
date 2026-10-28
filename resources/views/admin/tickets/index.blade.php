@extends('layouts.app-bootstrap')

@section('title', 'Support Tickets Management — Admin Panel')

@section('styles')
<style>
    .ticket-filter-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        cursor: pointer;
    }
    .ticket-filter-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }
    .ticket-filter-card.is-active {
        box-shadow: 0 0 0 2px var(--brand-primary), 0 0.5rem 1rem rgba(13, 110, 253, 0.15) !important;
    }
    [data-bs-theme="dark"] .ticket-filter-card.is-active {
        box-shadow: 0 0 0 2px var(--brand-primary), 0 0.5rem 1rem rgba(59, 130, 246, 0.25) !important;
    }
    [data-bs-theme="dark"] .btn-outline-secondary {
        color: #cbd5e1;
        border-color: #475569;
    }
    [data-bs-theme="dark"] .btn-outline-secondary:hover {
        background-color: #334155;
        border-color: #64748b;
        color: #ffffff;
    }
</style>
@endsection

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Support Tickets</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">
            <i class="bi bi-ticket-detailed-fill text-warning me-2"></i>Support Ticket Management
        </h2>
        <p class="text-muted mb-0 small">
            Review user support requests, escalate inquiries, and update resolution statuses.
        </p>
    </div>
</div>

@php
    $selectedStatus = request('status');
    $isAllTickets = empty($selectedStatus);
    $isOpen = $selectedStatus === 'Open';
    $isInProgress = $selectedStatus === 'In Progress';
    $isResolved = $selectedStatus === 'Resolved';
@endphp

<!-- Admin Ticket Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.tickets.index') }}" class="card text-decoration-none text-body shadow-sm border-0 border-start border-4 border-primary h-100 ticket-filter-card position-relative {{ $isAllTickets ? 'is-active' : '' }}" title="Show All Tickets">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="text-muted small fw-semibold text-uppercase">Total Tickets</div>
                    @if($isAllTickets)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small px-2 py-0">Active</span>
                    @endif
                </div>
                <div class="fs-4 fw-bold text-body">{{ $stats['total'] }}</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.tickets.index', ['status' => 'Open']) }}" class="card text-decoration-none text-body shadow-sm border-0 border-start border-4 border-danger h-100 ticket-filter-card position-relative {{ $isOpen ? 'is-active' : '' }}" title="Filter by Open Tickets">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="text-muted small fw-semibold text-uppercase">Open Tickets</div>
                    @if($isOpen)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle small px-2 py-0">Active</span>
                    @endif
                </div>
                <div class="fs-4 fw-bold text-danger">{{ $stats['open'] }}</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.tickets.index', ['status' => 'In Progress']) }}" class="card text-decoration-none text-body shadow-sm border-0 border-start border-4 border-warning h-100 ticket-filter-card position-relative {{ $isInProgress ? 'is-active' : '' }}" title="Filter by In Progress Tickets">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="text-muted small fw-semibold text-uppercase">In Progress</div>
                    @if($isInProgress)
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small px-2 py-0">Active</span>
                    @endif
                </div>
                <div class="fs-4 fw-bold text-warning-emphasis">{{ $stats['in_progress'] }}</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('admin.tickets.index', ['status' => 'Resolved']) }}" class="card text-decoration-none text-body shadow-sm border-0 border-start border-4 border-success h-100 ticket-filter-card position-relative {{ $isResolved ? 'is-active' : '' }}" title="Filter by Resolved Tickets">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="text-muted small fw-semibold text-uppercase">Resolved / Closed</div>
                    @if($isResolved)
                        <span class="badge bg-success-subtle text-success border border-success-subtle small px-2 py-0">Active</span>
                    @endif
                </div>
                <div class="fs-4 fw-bold text-success">{{ $stats['resolved'] }}</div>
            </div>
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.tickets.index') }}" class="row g-2 align-items-end">
            <!-- Search Query -->
            <div class="col-12 col-md-6 col-xl-3">
                <label for="searchQuery" class="form-label small fw-semibold mb-1">Search Tickets or Users</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control" id="searchQuery" name="q" value="{{ request('q') }}" placeholder="Subject, user name, email...">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-3 col-xl-2">
                <label for="filterStatus" class="form-label small fw-semibold mb-1">Status</label>
                <select class="form-select" id="filterStatus" name="status">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Category Filter -->
            <div class="col-6 col-md-3 col-xl-2">
                <label for="filterCategory" class="form-label small fw-semibold mb-1">Category</label>
                <select class="form-select" id="filterCategory" name="category">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Priority Filter -->
            <div class="col-6 col-md-3 col-xl-1">
                <label for="filterPriority" class="form-label small fw-semibold mb-1">Priority</label>
                <select class="form-select" id="filterPriority" name="priority">
                    <option value="">All Priorities</option>
                    @foreach($priorities as $pr)
                        <option value="{{ $pr }}" {{ request('priority') === $pr ? 'selected' : '' }}>{{ $pr }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Assigned Staff Filter -->
            <div class="col-6 col-md-3 col-xl-2">
                <label for="filterAssigned" class="form-label small fw-semibold mb-1">Assigned Agent</label>
                <select class="form-select" id="filterAssigned" name="assigned_to">
                    <option value="">All Staff</option>
                    <option value="unassigned" {{ request('assigned_to') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ request('assigned_to') == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Submit & Reset Buttons -->
            <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1 shadow-sm flex-grow-1" title="Apply Filters">
                    <i class="bi bi-funnel"></i>
                    <span>Filter</span>
                </button>
                @if(request()->hasAny(['q', 'status', 'category', 'priority', 'assigned_to']))
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 px-3" title="Reset Filters">
                        <i class="bi bi-x-circle"></i>
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Tickets Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-ticket-detailed me-1 text-primary"></i> All Support Tickets
            <span class="badge bg-secondary-subtle text-secondary ms-2">{{ $tickets->total() }} total</span>
        </h6>
    </div>

    @if($tickets->isEmpty())
        <div class="card-body text-center py-5">
            <div class="text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-semibold">No tickets match your filters</h5>
                <p class="small text-muted mb-0">Try clearing active filters to view all user requests.</p>
            </div>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th scope="col" style="width: 70px;">ID</th>
                        <th scope="col">User</th>
                        <th scope="col">Subject</th>
                        <th scope="col" style="width: 120px;">Category</th>
                        <th scope="col" style="width: 110px;">Priority</th>
                        <th scope="col" style="width: 130px;">Status</th>
                        <th scope="col" style="width: 130px;">Assigned</th>
                        <th scope="col" style="width: 130px;">Submitted</th>
                        <th scope="col" class="text-end" style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                        <tr>
                            <td class="text-muted small">#{{ $ticket->id }}</td>
                            <td>
                                <div class="fw-bold text-body">{{ $ticket->user->name ?? 'User' }}</div>
                                <div class="text-muted small">{{ $ticket->user->email ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <a href="{{ route('admin.tickets.show', $ticket) }}" class="fw-bold text-decoration-none text-body d-block">
                                    {{ $ticket->subject }}
                                </a>
                                @if($ticket->conversation_id)
                                    <span class="badge bg-light text-secondary border small" style="font-size: 0.7rem;">
                                        <i class="bi bi-robot me-1"></i>Chat Escalation
                                    </span>
                                @endif
                                @if($ticket->admin_response)
                                    <span class="badge bg-success-subtle text-success border small" style="font-size: 0.7rem;">
                                        <i class="bi bi-check2 me-1"></i>Responded
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-body-secondary text-body border">{{ $ticket->category }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $ticket->priorityBadgeClass }} px-2 py-1">
                                    {{ $ticket->priority }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $ticket->statusBadgeClass }} px-2 py-1">
                                    {{ $ticket->status }}
                                </span>
                            </td>
                            <td class="small">
                                @if($ticket->assignedTo)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                        <i class="bi bi-person-check me-1"></i>{{ $ticket->assignedTo->name }}
                                    </span>
                                @else
                                    <span class="badge bg-body-secondary text-secondary border px-2 py-1">
                                        Unassigned
                                    </span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                <div>{{ $ticket->created_at->format('M d, Y') }}</div>
                                <div class="text-muted small">{{ $ticket->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-gear me-1"></i>Manage
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="card-footer py-3 border-top d-flex justify-content-center">
                {{ $tickets->links('pagination::bootstrap-5') }}
            </div>
        @endif
    @endif
</div>
@endsection

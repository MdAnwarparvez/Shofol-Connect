@extends('layouts.app-bootstrap')

@section('title', 'My Support Tickets — Shofol Connect')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Support Tickets</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">
            <i class="bi bi-ticket-detailed text-primary me-2"></i>My Support Tickets
        </h2>
        <p class="text-muted mb-0 small">
            Track inquiries, requests, and human assistance escalated from the AI Assistant.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('tickets.create') }}" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-plus-circle"></i>
            <span>Create New Ticket</span>
        </a>
    </div>
</div>

<!-- Quick Ticket Metrics -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-primary">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold text-uppercase">Total Submitted</div>
                <div class="fs-4 fw-bold text-dark">{{ $tickets->total() }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-danger">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold text-uppercase">Open / In Progress</div>
                <div class="fs-4 fw-bold text-danger">{{ $openCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body py-3">
                <div class="text-muted small fw-semibold text-uppercase">Resolved / Closed</div>
                <div class="fs-4 fw-bold text-success">{{ $resolvedCount }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-list-task me-1 text-primary"></i> Support Requests
            <span class="badge bg-secondary-subtle text-secondary ms-2">{{ $tickets->total() }} total</span>
        </h6>
    </div>

    @if($tickets->isEmpty())
        <div class="card-body text-center py-5">
            <div class="text-muted">
                <i class="bi bi-ticket-perforated fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-semibold">No support tickets found</h5>
                <p class="small mb-3 text-muted">
                    Need help that the AI assistant couldn't solve? Submit a support ticket for our team.
                </p>
                <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Create Support Ticket
                </a>
            </div>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th scope="col" style="width: 70px;">ID</th>
                        <th scope="col">Subject</th>
                        <th scope="col" style="width: 130px;">Category</th>
                        <th scope="col" style="width: 110px;">Priority</th>
                        <th scope="col" style="width: 120px;">Status</th>
                        <th scope="col" style="width: 160px;">Submitted</th>
                        <th scope="col" class="text-end" style="width: 110px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                        <tr>
                            <td class="text-muted small">#{{ $ticket->id }}</td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" class="fw-bold text-decoration-none text-dark d-block">
                                    {{ $ticket->subject }}
                                </a>
                                @if($ticket->conversation_id)
                                    <span class="badge bg-light text-secondary border small" style="font-size: 0.7rem;">
                                        <i class="bi bi-robot me-1"></i>Chat Escalation
                                    </span>
                                @endif
                                @if($ticket->admin_response)
                                    <span class="badge bg-info-subtle text-info-emphasis border small" style="font-size: 0.7rem;">
                                        <i class="bi bi-chat-left-dots me-1"></i>Staff Responded
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $ticket->category }}</span>
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
                            <td class="small text-muted">
                                <div>{{ $ticket->created_at->format('M d, Y') }}</div>
                                <div class="text-muted small">{{ $ticket->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
                {{ $tickets->links('pagination::bootstrap-5') }}
            </div>
        @endif
    @endif
</div>
@endsection

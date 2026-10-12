@extends('layouts.app-bootstrap')

@section('title', 'Ticket #' . $ticket->id . ' — Shofol Connect Support')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}" class="text-decoration-none">Support Tickets</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ticket #{{ $ticket->id }}</li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0 mb-4">
            <!-- Ticket Header -->
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge {{ $ticket->statusBadgeClass }} px-2 py-1 fs-6">
                        {{ $ticket->status }}
                    </span>
                    <span class="badge {{ $ticket->priorityBadgeClass }} px-2 py-1">
                        {{ $ticket->priority }} Priority
                    </span>
                    <span class="badge bg-light text-dark border px-2 py-1">
                        {{ $ticket->category }}
                    </span>
                    <span class="text-muted small">Ticket #{{ $ticket->id }}</span>
                </div>

                <div>
                    <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back to My Tickets
                    </a>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                <!-- Subject -->
                <h3 class="fw-bold text-dark mb-3">
                    {{ $ticket->subject }}
                </h3>

                <!-- Metadata -->
                <div class="d-flex flex-wrap align-items-center gap-3 text-muted small pb-3 mb-4 border-bottom">
                    <div>
                        <i class="bi bi-calendar-check me-1"></i>
                        Submitted: {{ $ticket->created_at->format('M d, Y h:i A') }} ({{ $ticket->created_at->diffForHumans() }})
                    </div>
                    @if($ticket->resolved_at)
                        <div class="text-success fw-semibold">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            Resolved: {{ $ticket->resolved_at->format('M d, Y h:i A') }}
                        </div>
                    @endif
                    @if($ticket->conversation)
                        <div>
                            <i class="bi bi-robot me-1 text-primary"></i>
                            Escalated from:
                            <a href="{{ route('chat.show', $ticket->conversation) }}" class="text-decoration-none fw-semibold">
                                {{ $ticket->conversation->title }}
                            </a>
                        </div>
                    @endif
                </div>

                <!-- User Description -->
                <div class="mb-4">
                    <div class="text-muted small fw-bold text-uppercase mb-2">Description / Inquiry Details</div>
                    <div class="p-3 p-md-4 rounded bg-light border text-dark fs-6" style="white-space: pre-wrap; line-height: 1.7;">{{ $ticket->description }}</div>
                </div>

                <!-- Admin Response Section -->
                @if($ticket->admin_response)
                    <div class="card border-0 bg-success-subtle border-start border-4 border-success mt-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-2 mb-2 text-success-emphasis fw-bold">
                                <i class="bi bi-shield-check fs-5"></i>
                                <span>Official Support Response</span>
                            </div>
                            <div class="text-dark fs-6" style="white-space: pre-wrap; line-height: 1.7;">{{ $ticket->admin_response }}</div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-light border shadow-xs d-flex align-items-center gap-3 mt-4" role="alert">
                        <i class="bi bi-hourglass-split fs-4 text-warning"></i>
                        <div>
                            <div class="fw-semibold">Awaiting Staff Response</div>
                            <div class="small text-muted">Our support team is reviewing your ticket and will provide updates here.</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

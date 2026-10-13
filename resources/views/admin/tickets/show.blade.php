@extends('layouts.app-bootstrap')

@section('title', 'Manage Ticket #' . $ticket->id . ' — Admin Panel')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}" class="text-decoration-none">Support Tickets</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ticket #{{ $ticket->id }}</li>
            </ol>
        </nav>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-0">
                    <i class="bi bi-ticket-detailed-fill text-warning me-2"></i>Ticket #{{ $ticket->id }}: {{ $ticket->subject }}
                </h3>
            </div>
            <div>
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to All Tickets
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left: Ticket Details & Update Form -->
            <div class="col-lg-8">
                <!-- User Inquiry Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $ticket->statusBadgeClass }} px-2 py-1 fs-6">{{ $ticket->status }}</span>
                            <span class="badge {{ $ticket->priorityBadgeClass }} px-2 py-1">{{ $ticket->priority }} Priority</span>
                            <span class="badge bg-light text-dark border">{{ $ticket->category }}</span>
                            @if($ticket->assignedTo)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    <i class="bi bi-person-check me-1"></i>Assigned: {{ $ticket->assignedTo->name }}
                                </span>
                            @else
                                <span class="badge bg-light text-muted border px-2 py-1">
                                    Unassigned
                                </span>
                            @endif
                        </div>
                        <span class="text-muted small">Submitted {{ $ticket->created_at->format('M d, Y h:i A') }}</span>
                    </div>

                    <div class="card-body p-4">
                        <h5 class="fw-bold text-dark mb-3">User Inquiry Details</h5>
                        <div class="p-3 rounded bg-light border text-dark fs-6 mb-4" style="white-space: pre-wrap; line-height: 1.7;">{{ $ticket->description }}</div>

                        <!-- Escalated Conversation Preview if present -->
                        @if($ticket->conversation)
                            <div class="card border-0 bg-light-subtle border-start border-4 border-info mb-3">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-bold text-info-emphasis">
                                            <i class="bi bi-robot me-1"></i> Escalated from AI Assistant (Session #{{ $ticket->conversation->id }})
                                        </div>
                                        <a href="{{ route('admin.conversations.show', $ticket->conversation) }}" class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Inspect Chat Session
                                        </a>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        <strong>Topic:</strong> {{ $ticket->conversation->title }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Admin Action & Resolution Form -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-shield-check text-success me-2"></i>Staff Response & Resolution
                        </h5>
                    </div>

                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}">
                            @csrf
                            @method('PUT')

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="status" class="form-label fw-semibold">
                                        Update Status <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                        @foreach($statuses as $st)
                                            <option value="{{ $st }}" {{ old('status', $ticket->status) === $st ? 'selected' : '' }}>
                                                {{ $st }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="priority" class="form-label fw-semibold">
                                        Update Priority <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                        @foreach($priorities as $pr)
                                            <option value="{{ $pr }}" {{ old('priority', $ticket->priority) === $pr ? 'selected' : '' }}>
                                                {{ $pr }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="assigned_to" class="form-label fw-semibold">
                                        Assign Staff Member
                                    </label>
                                    <select class="form-select @error('assigned_to') is-invalid @enderror" id="assigned_to" name="assigned_to">
                                        <option value="">-- Unassigned --</option>
                                        @foreach($admins as $adm)
                                            <option value="{{ $adm->id }}" {{ old('assigned_to', $ticket->assigned_to) == $adm->id ? 'selected' : '' }}>
                                                {{ $adm->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('assigned_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="admin_response" class="form-label fw-semibold">
                                        Staff Response / Resolution Notes
                                    </label>
                                    <textarea
                                        class="form-control @error('admin_response') is-invalid @enderror"
                                        id="admin_response"
                                        name="admin_response"
                                        rows="6"
                                        placeholder="Enter the official response, explanation, or instructions for the user..."
                                    >{{ old('admin_response', $ticket->admin_response) }}</textarea>
                                    @error('admin_response')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text small">This message will be visible to the user on their ticket details page.</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.tickets.index') }}" class="btn btn-light border px-4">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-check-circle me-1"></i>Save Ticket Updates
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right: User Information & Metadata Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-person-circle text-primary me-2"></i>Ticket Submitter
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="bg-primary-subtle text-primary rounded-circle p-3 fs-4 lh-1">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">{{ $ticket->user->name ?? 'User' }}</h6>
                                <div class="text-muted small">{{ $ticket->user->email ?? 'N/A' }}</div>
                                <span class="badge {{ $ticket->user && $ticket->user->isAdmin() ? 'bg-danger' : 'bg-primary' }} mt-1">
                                    {{ ucfirst($ticket->user->role ?? 'User') }}
                                </span>
                            </div>
                        </div>

                        <hr>

                        <div class="small">
                            <div class="d-flex justify-content-between py-1 text-muted">
                                <span>Ticket Created:</span>
                                <span class="fw-semibold text-dark">{{ $ticket->created_at->format('M d, Y h:i A') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 text-muted">
                                <span>Last Updated:</span>
                                <span class="fw-semibold text-dark">{{ $ticket->updated_at->diffForHumans() }}</span>
                            </div>
                            @if($ticket->resolved_at)
                                <div class="d-flex justify-content-between py-1 text-muted">
                                    <span>Resolved At:</span>
                                    <span class="fw-semibold text-success">{{ $ticket->resolved_at->format('M d, Y h:i A') }}</span>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between py-1 text-muted">
                                <span>Total User Tickets:</span>
                                <span class="fw-semibold text-dark">{{ $ticket->user->supportTickets()->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app-bootstrap')

@section('title', 'AI Conversations Monitoring — Admin Panel')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">AI Conversations</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div>
                <h3 class="fw-bold mb-0">
                    <i class="bi bi-chat-left-dots-fill text-primary me-2"></i>AI Conversation Monitoring
                </h3>
                <p class="text-muted small mb-0">
                    Audit and review user AI assistant dialogues, message volumes, and support escalation triggers.
                </p>
            </div>
            <div>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-subtle p-3 text-primary fs-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Conversations</div>
                            <div class="fs-4 fw-bold text-dark">{{ number_format($stats['total']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-info-subtle p-3 text-info-emphasis fs-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-chat-square-text"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Chat Messages</div>
                            <div class="fs-4 fw-bold text-dark">{{ number_format($stats['messages_count']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-body p-3 d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning-subtle p-3 text-warning-emphasis fs-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-ticket-perforated-fill"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Escalated to Tickets</div>
                            <div class="fs-4 fw-bold text-dark">{{ number_format($stats['escalated']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('admin.conversations.index') }}" class="row g-2 align-items-center">
                    <div class="col-md-6 col-lg-7">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input
                                type="text"
                                name="q"
                                class="form-control border-start-0"
                                placeholder="Search by topic title, employee name, or email..."
                                value="{{ request('q') }}"
                            >
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <select name="escalated" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Conversations</option>
                            <option value="yes" {{ request('escalated') === 'yes' ? 'selected' : '' }}>Escalated to Ticket</option>
                            <option value="no" {{ request('escalated') === 'no' ? 'selected' : '' }}>Non-Escalated</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Filter
                        </button>
                        @if(request()->hasAny(['q', 'escalated']))
                            <a href="{{ route('admin.conversations.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Conversations Table -->
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Employee / User</th>
                            <th>Topic / Title</th>
                            <th class="text-center">Messages</th>
                            <th>Escalation</th>
                            <th>Last Active</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($conversations as $conv)
                            <tr>
                                <td class="fw-bold text-muted small">#{{ $conv->id }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $conv->user?->name ?? 'Deleted User' }}</div>
                                    <div class="text-muted small">{{ $conv->user?->email ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark">{{ $conv->title }}</span>
                                    @if($conv->latestMessage)
                                        <div class="text-muted small text-truncate" style="max-width: 320px;">
                                            <span class="text-uppercase" style="font-size: 0.65rem;">{{ $conv->latestMessage->role }}:</span>
                                            {{ \Illuminate\Support\Str::limit($conv->latestMessage->content, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="bi bi-chat-text me-1 text-primary"></i>{{ $conv->messages_count }}
                                    </span>
                                </td>
                                <td>
                                    @if($conv->supportTickets()->exists())
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                            <i class="bi bi-ticket-perforated me-1"></i>Ticket Opened
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border px-2 py-1">
                                            Self-Resolved
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ $conv->updated_at->format('M d, Y h:i A') }}
                                    <div style="font-size: 0.75rem;">{{ $conv->updated_at->diffForHumans() }}</div>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('admin.conversations.show', $conv) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i>Review Transcript
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-chat-square-dots display-6 d-block mb-2 text-muted"></i>
                                    <div class="fw-semibold">No AI conversations found.</div>
                                    <div class="small">No sessions match the current search or filter criteria.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($conversations->hasPages())
                <div class="card-footer bg-white py-3 border-top">
                    {{ $conversations->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

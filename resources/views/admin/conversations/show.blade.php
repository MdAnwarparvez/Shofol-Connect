@extends('layouts.app-bootstrap')

@section('title', 'Audit Conversation #' . $conversation->id . ' — Admin Panel')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Admin Panel</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.conversations.index') }}" class="text-decoration-none">AI Conversations</a></li>
                <li class="breadcrumb-item active" aria-current="page">Session #{{ $conversation->id }}</li>
            </ol>
        </nav>

        <!-- Privacy & Audit Warning Banner -->
        <div class="alert alert-info border shadow-xs d-flex align-items-center gap-3 mb-4" role="alert">
            <i class="bi bi-shield-lock-fill fs-3 text-primary"></i>
            <div>
                <div class="fw-bold">Administrative Read-Only Audit Mode</div>
                <div class="small">
                    You are reviewing this conversation record for support audit and quality assurance purposes. Messages cannot be modified or injected into the active user session.
                </div>
            </div>
        </div>

        <!-- Conversation Header Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="fw-bold text-dark mb-1">
                        <i class="bi bi-chat-quote-fill text-primary me-2"></i>{{ $conversation->title }}
                    </h4>
                    <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                        <span><i class="bi bi-person-fill me-1"></i>User: <strong>{{ $conversation->user?->name ?? 'Deleted User' }}</strong> ({{ $conversation->user?->email ?? 'N/A' }})</span>
                        <span><i class="bi bi-calendar3 me-1"></i>Started: {{ $conversation->created_at->format('M d, Y h:i A') }}</span>
                        <span><i class="bi bi-chat-left-text me-1"></i>Total Messages: {{ $conversation->messages->count() }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.conversations.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back to List
                    </a>
                </div>
            </div>

            <!-- Escalated Support Tickets Card if any -->
            @if($conversation->supportTickets->isNotEmpty())
                <div class="card-body bg-warning-subtle border-bottom p-3">
                    <div class="d-flex align-items-center gap-2 text-warning-emphasis fw-bold mb-2">
                        <i class="bi bi-ticket-perforated-fill fs-5"></i>
                        <span>Escalated Support Tickets from this Conversation ({{ $conversation->supportTickets->count() }})</span>
                    </div>
                    <div class="row g-2">
                        @foreach($conversation->supportTickets as $ticket)
                            <div class="col-md-6">
                                <div class="p-2 rounded bg-white border d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge {{ $ticket->statusBadgeClass }} me-1">{{ $ticket->status }}</span>
                                        <span class="fw-semibold small text-dark">Ticket #{{ $ticket->id }}:</span>
                                        <span class="small text-muted">{{ \Illuminate\Support\Str::limit($ticket->subject, 30) }}</span>
                                    </div>
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem;">
                                        Manage
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Transcript Body -->
            <div class="card-body p-4 p-md-5" style="background-color: #f8fafc;">
                <div class="d-flex flex-column gap-4">
                    @forelse($conversation->messages as $msg)
                        <div class="d-flex {{ $msg->role === 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                            <div class="d-flex flex-column" style="max-width: 85%;">
                                <div class="d-flex align-items-center gap-2 mb-1 {{ $msg->role === 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                                    @if($msg->role === 'user')
                                        <span class="badge bg-primary text-white" style="font-size: 0.7rem;">
                                            <i class="bi bi-person me-1"></i>{{ $conversation->user?->name ?? 'User' }}
                                        </span>
                                    @else
                                        <span class="badge bg-dark text-white" style="font-size: 0.7rem;">
                                            <i class="bi bi-robot me-1 text-primary"></i>AI Assistant
                                        </span>
                                    @endif
                                    <span class="text-muted" style="font-size: 0.75rem;">{{ $msg->created_at->format('h:i A') }}</span>
                                </div>

                                <div class="p-3 rounded-3 shadow-xs {{ $msg->role === 'user' ? 'bg-primary text-white' : 'bg-white text-dark border' }}">
                                    @if($msg->role === 'user')
                                        <div style="white-space: pre-wrap; line-height: 1.6;">{{ $msg->content }}</div>
                                    @else
                                        <div class="assistant-content" style="line-height: 1.6;">
                                            {!! \Illuminate\Support\Str::markdown($msg->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                        </div>

                                        <!-- Citations -->
                                        @if(!empty($msg->sources))
                                            <div class="mt-3 pt-2 border-top small">
                                                <div class="text-muted fw-bold mb-1" style="font-size: 0.75rem;">
                                                    <i class="bi bi-shield-check text-success me-1"></i>Retrieved Policy Citations:
                                                </div>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($msg->sources as $source)
                                                        <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">
                                                            [{{ $source['module'] ?? 'Policy' }}] {{ $source['title'] ?? 'Verified Source' }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-chat-dots display-6 d-block mb-2 text-muted"></i>
                            <div>No messages found in this session transcript.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

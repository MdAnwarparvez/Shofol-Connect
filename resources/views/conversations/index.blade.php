@extends('layouts.app-bootstrap')

@section('title', 'Conversation History — Shofol Connect')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Conversation History</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">
            <i class="bi bi-clock-history text-primary me-2"></i>Conversation History
        </h2>
        <p class="text-muted mb-0 small">
            Review and resume your previous interactions with the Shofol Connect AI Assistant.
        </p>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('chat.new') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-plus-circle"></i>
                <span>Start New Chat</span>
            </button>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-chat-left-text me-1 text-primary"></i> Previous Sessions
            <span class="badge bg-secondary-subtle text-secondary ms-2">{{ $conversations->total() }} total</span>
        </h6>
    </div>

    @if($conversations->isEmpty())
        <div class="card-body text-center py-5">
            <div class="text-muted">
                <i class="bi bi-chat-square-dots fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-semibold">No conversation history yet</h5>
                <p class="small mb-3 text-muted">
                    Start a conversation with the AI Assistant to ask questions regarding HRM, Payroll, CRM, or Accounting.
                </p>
                <a href="{{ route('chat.index') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-robot me-1"></i>Start Your First Conversation
                </a>
            </div>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th scope="col" style="width: 50px;">#</th>
                        <th scope="col">Conversation Topic</th>
                        <th scope="col">Latest Snippet</th>
                        <th scope="col" style="width: 130px;">Messages</th>
                        <th scope="col" style="width: 170px;">Last Activity</th>
                        <th scope="col" class="text-end" style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($conversations as $index => $conv)
                        <tr>
                            <td class="text-muted small">
                                {{ $conversations->firstItem() + $index }}
                            </td>
                            <td>
                                <a href="{{ route('chat.show', $conv) }}" class="fw-bold text-decoration-none text-dark d-block">
                                    {{ $conv->title }}
                                </a>
                                <span class="text-muted small" style="font-size: 0.75rem;">
                                    Started {{ $conv->created_at->format('M d, Y') }}
                                </span>
                            </td>
                            <td>
                                @if($conv->latestMessage)
                                    <div class="text-muted small text-truncate" style="max-width: 320px;">
                                        @if($conv->latestMessage->role === 'user')
                                            <i class="bi bi-person me-1 text-primary"></i>
                                        @else
                                            <i class="bi bi-robot me-1 text-info"></i>
                                        @endif
                                        {{ Str::limit($conv->latestMessage->content, 70) }}
                                    </div>
                                @else
                                    <span class="text-muted fst-italic small">No messages yet</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    <i class="bi bi-chat-dots me-1 text-secondary"></i>{{ $conv->messages_count }} {{ Str::plural('message', $conv->messages_count) }}
                                </span>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">{{ $conv->updated_at->diffForHumans() }}</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ $conv->updated_at->format('M d, Y h:i A') }}</div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('chat.show', $conv) }}" class="btn btn-outline-primary" title="Resume and Continue Chat">
                                        <i class="bi bi-box-arrow-in-up-right me-1"></i>Continue
                                    </a>
                                    <form method="POST" action="{{ route('conversations.destroy', $conv) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this conversation: \'{{ addslashes($conv->title) }}\'?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete Conversation">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($conversations->hasPages())
            <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
                {{ $conversations->links('pagination::bootstrap-5') }}
            </div>
        @endif
    @endif
</div>
@endsection

@extends('layouts.app-bootstrap')

@section('title', 'AI Support Assistant — Shofol Connect')

@section('styles')
<style>
    .chat-container {
        max-width: 950px;
        margin: 0 auto;
    }
    .chat-card {
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        background: #ffffff;
    }
    .chat-header {
        background: #ffffff;
        border-bottom: 1px solid #edf2f7;
        padding: 1rem 1.5rem;
    }
    .chat-messages {
        height: 520px;
        overflow-y: auto;
        padding: 1.5rem;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .message-row {
        display: flex;
        gap: 0.75rem;
        max-width: 85%;
    }
    .message-row.user {
        align-self: flex-end;
        flex-direction: row-reverse;
    }
    .message-row.assistant {
        align-self: flex-start;
    }
    .avatar-icon {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }
    .avatar-assistant {
        background: #e0f2fe;
        color: #0284c7;
    }
    .avatar-user {
        background: #ede9fe;
        color: #7c3aed;
    }
    .message-bubble {
        padding: 0.85rem 1.15rem;
        border-radius: 1rem;
        font-size: 0.95rem;
        line-height: 1.6;
        word-break: break-word;
    }
    .message-bubble.user {
        background: #0d6efd;
        color: #ffffff;
        border-bottom-right-radius: 0.25rem;
    }
    .message-bubble.assistant {
        background: #ffffff;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 0.25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }
    .sources-box {
        margin-top: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: #f1f5f9;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        color: #475569;
    }
    .prompt-chip {
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.85rem;
    }
    .prompt-chip:hover {
        background: #e2e8f0 !important;
        transform: translateY(-1px);
    }
    .typing-indicator {
        display: none;
        align-self: flex-start;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 0.75rem 1rem;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }
</style>
@endsection

@section('content')
<div class="chat-container">
    <!-- Breadcrumb & Header info -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">AI Support Assistant</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0">
                <i class="bi bi-robot text-primary me-2"></i>Shofol Connect AI Assistant
            </h4>
            <div class="text-muted small mt-1">
                <span class="fw-semibold text-dark" id="activeConversationTitle">{{ $conversation->title }}</span>
                <span class="mx-1">&bull;</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0">Active</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                <i class="bi bi-shield-check me-1"></i>RAG Grounded
            </span>
            <a href="{{ route('tickets.create', ['conversation_id' => $conversation->id]) }}" class="btn btn-outline-warning btn-sm" id="escalateTicketBtn" title="Create a support ticket from this conversation">
                <i class="bi bi-ticket-perforated me-1"></i>Contact Support
            </a>
            <a href="{{ route('conversations.index') }}" class="btn btn-outline-secondary btn-sm" title="View previous conversation sessions">
                <i class="bi bi-clock-history me-1"></i>History
            </a>
            <form method="POST" action="{{ route('chat.new') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm" title="Start a fresh conversation">
                    <i class="bi bi-plus-lg me-1"></i>New Chat
                </button>
            </form>
        </div>
    </div>

    <!-- Suggested Quick Prompts -->
    <div class="mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small fw-semibold"><i class="bi bi-lightbulb me-1"></i>Suggested Topics:</span>
            <span class="badge bg-light text-dark border prompt-chip p-2" onclick="fillPrompt('How do full-time employees request annual or casual leave?')">
                <i class="bi bi-people me-1 text-primary"></i>Leave Policy (HRM)
            </span>
            <span class="badge bg-light text-dark border prompt-chip p-2" onclick="fillPrompt('When is monthly salary disbursed and how is tax deduction computed?')">
                <i class="bi bi-cash-stack me-1 text-success"></i>Salary & Tax (Payroll)
            </span>
            <span class="badge bg-light text-dark border prompt-chip p-2" onclick="fillPrompt('What are the official sales pipeline stages and criteria to qualify a lead?')">
                <i class="bi bi-funnel me-1 text-info"></i>Sales Pipeline (CRM)
            </span>
            <span class="badge bg-light text-dark border prompt-chip p-2" onclick="fillPrompt('How do we issue customer invoices and what are the standard payment terms?')">
                <i class="bi bi-receipt me-1 text-warning"></i>Invoicing Terms (Accounting)
            </span>
        </div>
    </div>

    <!-- Chat Card -->
    <div class="card chat-card">
        <!-- Messages Feed Area -->
        <div class="chat-messages" id="chatMessages">
            <!-- Initial Greeting from AI Assistant -->
            <div class="message-row assistant">
                <div class="avatar-icon avatar-assistant">
                    <i class="bi bi-robot"></i>
                </div>
                <div>
                    <div class="message-bubble assistant">
                        <strong>Hello {{ Auth::user()->name }}!</strong> I am your Shofol Connect AI Support Assistant.
                        <p class="mb-1 mt-2">
                            I can answer your questions strictly from our official knowledge base across:
                        </p>
                        <ul class="mb-1 ps-3 small text-muted">
                            <li><strong>HRM:</strong> Leave policies, onboarding, employee benefits</li>
                            <li><strong>Payroll:</strong> Salary disbursement dates, tax deductions, provident fund</li>
                            <li><strong>CRM:</strong> Sales pipeline stages, lead qualification</li>
                            <li><strong>Accounting:</strong> Customer invoice terms, payment reconciliation</li>
                        </ul>
                        <div class="mt-2 small text-muted">
                            <i class="bi bi-info-circle me-1"></i>If a question is outside our knowledge base, I will guide you to our support team.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Existing Stored Messages -->
            @foreach($messages as $msg)
                <div class="message-row {{ $msg->role }}">
                    <div class="avatar-icon {{ $msg->role === 'user' ? 'avatar-user' : 'avatar-assistant' }}">
                        <i class="bi {{ $msg->role === 'user' ? 'bi-person-fill' : 'bi-robot' }}"></i>
                    </div>
                    <div>
                        <div class="message-bubble {{ $msg->role }}">
                            @if($msg->role === 'assistant' && str_contains($msg->content, 'Verified Policy:'))
                                <div class="mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                        <i class="bi bi-file-earmark-check me-1"></i>Official Knowledge Base Policy
                                    </span>
                                </div>
                            @endif
                            {!! nl2br(e($msg->content)) !!}
                        </div>
                        @if($msg->role === 'assistant' && !empty($msg->sources))
                            <div class="sources-box">
                                <div class="fw-semibold small mb-1"><i class="bi bi-journal-check text-primary me-1"></i>Verified Sources:</div>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($msg->sources as $src)
                                        <span class="badge bg-white text-secondary border">
                                            [{{ $src['module'] }}] {{ $src['title'] }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <div class="text-muted small mt-1 {{ $msg->role === 'user' ? 'text-end' : '' }}" style="font-size: 0.75rem;">
                            {{ $msg->created_at->format('h:i A') }}
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Typing indicator -->
            <div class="message-row assistant typing-indicator" id="typingIndicator">
                <div class="avatar-icon avatar-assistant">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="message-bubble assistant d-flex align-items-center gap-2">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="text-muted small">Searching knowledge base and formulating response...</span>
                </div>
            </div>
        </div>

        <!-- Chat Input Bar -->
        <div class="card-footer bg-white border-top p-3">
            <div id="errorAlert" class="alert alert-danger alert-dismissible fade show d-none small py-2 mb-2" role="alert">
                <span id="errorMessage">An error occurred.</span>
                <button type="button" class="btn-close py-2" onclick="dismissError()"></button>
            </div>

            <form id="chatForm" method="POST" action="{{ route('chat.send') }}">
                @csrf
                <input type="hidden" name="conversation_id" id="conversationId" value="{{ $conversation->id }}">

                <div class="input-group">
                    <input
                        type="text"
                        name="message"
                        id="messageInput"
                        class="form-control py-2 px-3"
                        placeholder="Ask a question about HRM, Payroll, CRM, or Accounting..."
                        autocomplete="off"
                        required
                    >
                    <button type="submit" id="sendBtn" class="btn btn-primary px-4 d-flex align-items-center gap-2">
                        <span id="sendBtnText">Send</span>
                        <i id="sendBtnIcon" class="bi bi-send-fill"></i>
                        <span id="sendBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </form>
            <div class="d-flex justify-content-between align-items-center mt-2 px-1 text-muted" style="font-size: 0.75rem;">
                <span>Responses are strictly grounded in Shofol Connect documentation.</span>
                <span>Press Enter to send</span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const sendBtnText = document.getElementById('sendBtnText');
    const sendBtnIcon = document.getElementById('sendBtnIcon');
    const sendBtnSpinner = document.getElementById('sendBtnSpinner');
    const typingIndicator = document.getElementById('typingIndicator');
    const errorAlert = document.getElementById('errorAlert');
    const errorMessage = document.getElementById('errorMessage');
    const conversationIdInput = document.getElementById('conversationId');

    // Scroll to bottom on load
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    scrollToBottom();

    // Populate suggested prompt into input
    function fillPrompt(text) {
        messageInput.value = text;
        messageInput.focus();
    }

    function dismissError() {
        errorAlert.classList.add('d-none');
    }

    function showError(msg) {
        errorMessage.textContent = msg;
        errorAlert.classList.remove('d-none');
    }

    // Append a user message element
    function appendUserMessage(text, time) {
        const row = document.createElement('div');
        row.className = 'message-row user';
        row.innerHTML = `
            <div class="avatar-icon avatar-user">
                <i class="bi bi-person-fill"></i>
            </div>
            <div>
                <div class="message-bubble user">${escapeHtml(text)}</div>
                <div class="text-muted small mt-1 text-end" style="font-size: 0.75rem;">${time || 'Just now'}</div>
            </div>
        `;
        // Insert before typing indicator
        chatMessages.insertBefore(row, typingIndicator);
        scrollToBottom();
    }

    // Append an assistant message element
    function appendAssistantMessage(text, sources, time, isFallback, convId, isPolicyFallback) {
        const row = document.createElement('div');
        row.className = 'message-row assistant';

        let badgeHtml = '';
        if (isPolicyFallback || text.includes('Verified Policy:')) {
            badgeHtml = `
                <div class="mb-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                        <i class="bi bi-file-earmark-check me-1"></i>Official Knowledge Base Policy
                    </span>
                </div>
            `;
        }

        let sourcesHtml = '';
        if (sources && sources.length > 0) {
            const badges = sources.map(s => `<span class="badge bg-white text-secondary border">[${escapeHtml(s.module)}] ${escapeHtml(s.title)}</span>`).join(' ');
            sourcesHtml = `
                <div class="sources-box">
                    <div class="fw-semibold small mb-1"><i class="bi bi-journal-check text-primary me-1"></i>Verified Sources:</div>
                    <div class="d-flex flex-wrap gap-1">${badges}</div>
                </div>
            `;
        }

        let fallbackButtonHtml = '';
        if (isFallback) {
            const cId = convId || conversationIdInput.value;
            fallbackButtonHtml = `
                <div class="mt-2 pt-2 border-top">
                    <a href="{{ route('tickets.create') }}?conversation_id=${encodeURIComponent(cId)}" class="btn btn-sm btn-outline-warning text-dark fw-semibold">
                        <i class="bi bi-ticket-perforated me-1 text-danger"></i>Create Support Ticket from this Inquiry
                    </a>
                </div>
            `;
        }

        row.innerHTML = `
            <div class="avatar-icon avatar-assistant">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <div class="message-bubble assistant">
                    ${badgeHtml}
                    <div>${escapeHtml(text).replace(/\\n/g, '<br>')}</div>
                    ${fallbackButtonHtml}
                </div>
                ${sourcesHtml}
                <div class="text-muted small mt-1" style="font-size: 0.75rem;">${time || 'Just now'}</div>
            </div>
        `;
        chatMessages.insertBefore(row, typingIndicator);
        scrollToBottom();
    }

    function escapeHtml(string) {
        const div = document.createElement('div');
        div.innerText = string;
        return div.innerHTML;
    }

    function setLoading(isLoading) {
        if (isLoading) {
            sendBtn.disabled = true;
            messageInput.disabled = true;
            sendBtnText.textContent = 'Thinking...';
            sendBtnIcon.classList.add('d-none');
            sendBtnSpinner.classList.remove('d-none');
            typingIndicator.style.display = 'flex';
            scrollToBottom();
        } else {
            sendBtn.disabled = false;
            messageInput.disabled = false;
            sendBtnText.textContent = 'Send';
            sendBtnIcon.classList.remove('d-none');
            sendBtnSpinner.classList.add('d-none');
            typingIndicator.style.display = 'none';
            messageInput.focus();
        }
    }

    // Form submit via AJAX fetch
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        dismissError();

        const message = messageInput.value.trim();
        if (!message) return;

        appendUserMessage(message, new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
        messageInput.value = '';
        setLoading(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch("{{ route('chat.send') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    message: message,
                    conversation_id: conversationIdInput.value,
                })
            });

            if (!response.ok) {
                const errData = await response.json().catch(() => ({}));
                throw new Error(errData.message || `Server error (${response.status})`);
            }

            const data = await response.json();

            if (data.conversation_id) {
                conversationIdInput.value = data.conversation_id;
                const escalateBtn = document.getElementById('escalateTicketBtn');
                if (escalateBtn) {
                    escalateBtn.href = "{{ route('tickets.create') }}?conversation_id=" + encodeURIComponent(data.conversation_id);
                }
            }

            if (data.conversation_title) {
                const titleSpan = document.getElementById('activeConversationTitle');
                if (titleSpan) {
                    titleSpan.textContent = data.conversation_title;
                }
            }

            if (data.assistant_message) {
                appendAssistantMessage(
                    data.assistant_message.content,
                    data.assistant_message.sources,
                    data.assistant_message.created_at,
                    data.assistant_message.fallback,
                    data.conversation_id || conversationIdInput.value,
                    data.assistant_message.is_policy_fallback
                );
            } else {
                throw new Error('Unexpected response format from server.');
            }
        } catch (err) {
            console.error(err);
            showError('The AI assistant is temporarily unavailable. Please try again in a moment or open a support ticket.');
        } finally {
            setLoading(false);
            scrollToBottom();
        }
    });
</script>
@endsection

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
    .markdown-content {
        line-height: 1.6;
        word-break: break-word;
    }
    .markdown-content p:last-child {
        margin-bottom: 0;
    }
    .markdown-content ul, .markdown-content ol {
        margin-top: 0.25rem;
        margin-bottom: 0.5rem;
        padding-left: 1.25rem;
    }
    .markdown-content li {
        margin-bottom: 0.2rem;
    }
    .markdown-content h1, .markdown-content h2, .markdown-content h3,
    .markdown-content h4, .markdown-content h5, .markdown-content h6 {
        font-size: 1.05rem;
        font-weight: 600;
        margin-top: 0.5rem;
        margin-bottom: 0.35rem;
        color: #0f172a;
    }
    .markdown-content pre {
        background: #f1f5f9;
        padding: 0.5rem 0.75rem;
        border-radius: 0.375rem;
        overflow-x: auto;
        font-size: 0.85rem;
        margin-top: 0.35rem;
        margin-bottom: 0.5rem;
        border: 1px solid #e2e8f0;
    }
    .markdown-content code {
        background: #f1f5f9;
        color: #0f172a;
        padding: 0.15rem 0.3rem;
        border-radius: 0.25rem;
        font-size: 0.875em;
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .markdown-content pre code {
        background: transparent;
        padding: 0;
        border: 0;
    }
    .markdown-content a {
        color: #0d6efd;
        text-decoration: underline;
    }
    .markdown-content a:hover {
        color: #0a58ca;
    }
    .markdown-content blockquote {
        border-left: 3px solid #cbd5e1;
        padding-left: 0.75rem;
        margin: 0.5rem 0;
        color: #64748b;
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
                            @if($msg->role === 'assistant')
                                <div class="markdown-content">
                                    {!! \Illuminate\Support\Str::markdown($msg->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                </div>
                                @if($msg->isSupportTicketEligible())
                                    <div class="mt-2 pt-2 border-top">
                                        <a href="{{ route('tickets.create', ['conversation_id' => $conversation->id]) }}" class="btn btn-sm btn-outline-warning text-dark fw-semibold">
                                            <i class="bi bi-ticket-perforated me-1 text-danger"></i>Create Support Ticket from this Inquiry
                                        </a>
                                    </div>
                                @endif
                            @else
                                <div>{!! nl2br(e($msg->content)) !!}</div>
                            @endif
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
                    <textarea
                        name="message"
                        id="messageInput"
                        class="form-control py-2 px-3"
                        placeholder="Ask a question about HRM, Payroll, CRM, or Accounting..."
                        rows="1"
                        maxlength="1000"
                        style="resize: none; overflow-y: hidden; min-height: 42px; max-height: 160px; line-height: 1.5;"
                        required
                    ></textarea>
                    <button type="submit" id="sendBtn" class="btn btn-primary px-4 d-flex align-items-center gap-2">
                        <span id="sendBtnText">Send</span>
                        <i id="sendBtnIcon" class="bi bi-send-fill"></i>
                        <span id="sendBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </form>
            <div class="d-flex justify-content-between align-items-center mt-2 px-1 text-muted" style="font-size: 0.75rem;">
                <span>Responses are strictly grounded in Shofol Connect documentation.</span>
                <span>Press Enter to send, Shift + Enter for new line</span>
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

    let isSubmitting = false;

    // Scroll to bottom on load
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    scrollToBottom();

    // Auto-resize textarea according to content (min 42px, max 160px)
    function autoResizeInput() {
        if (!messageInput) return;
        messageInput.style.height = 'auto';
        const newHeight = Math.min(Math.max(messageInput.scrollHeight, 42), 160);
        messageInput.style.height = newHeight + 'px';
        if (messageInput.scrollHeight > 160) {
            messageInput.style.overflowY = 'auto';
        } else {
            messageInput.style.overflowY = 'hidden';
        }
    }

    messageInput.addEventListener('input', autoResizeInput);

    // Populate suggested prompt into input
    function fillPrompt(text) {
        messageInput.value = text;
        autoResizeInput();
        messageInput.focus();
    }

    function dismissError() {
        errorAlert.classList.add('d-none');
    }

    function showError(msg) {
        errorMessage.textContent = msg;
        errorAlert.classList.remove('d-none');
    }

    // Escape raw HTML entities
    function escapeHtml(string) {
        if (!string) return '';
        const div = document.createElement('div');
        div.innerText = string;
        return div.innerHTML;
    }

    // Safe client-side Markdown parser (escapes HTML first to prevent XSS)
    function renderSafeMarkdown(text) {
        if (!text) return '';

        // Step 1: Escape all HTML entities first
        let safe = escapeHtml(text);

        // Step 2: Code blocks (```lang ... ```)
        safe = safe.replace(/```([a-zA-Z0-9_-]*)\n?([\s\S]*?)```/g, function(match, lang, code) {
            return `<pre><code class="language-${lang || 'plaintext'}">${code.trim()}</code></pre>`;
        });

        // Step 3: Inline code (`code`)
        safe = safe.replace(/`([^`\n]+)`/g, '<code>$1</code>');

        // Step 4: Headings (#, ##, ###)
        safe = safe.replace(/^### (.*$)/gim, '<h6>$1</h6>');
        safe = safe.replace(/^## (.*$)/gim, '<h5>$1</h5>');
        safe = safe.replace(/^# (.*$)/gim, '<h4>$1</h4>');

        // Step 5: Bold & Italic
        safe = safe.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        safe = safe.replace(/__([^_]+)__/g, '<strong>$1</strong>');
        safe = safe.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        safe = safe.replace(/_([^_]+)_/g, '<em>$1</em>');

        // Step 6: Safe links [text](url) where url is http, https, or relative
        safe = safe.replace(/\[([^\]]+)\]\((https?:\/\/[^\s\)]+|\/[^\s\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');

        // Step 7: Lists (unordered: -, *; ordered: 1., 2.)
        const lines = safe.split('\n');
        let inUl = false;
        let inOl = false;
        const processedLines = [];

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            const ulMatch = line.match(/^(\s*)[-*]\s+(.*)$/);
            const olMatch = line.match(/^(\s*)\d+\.\s+(.*)$/);

            if (ulMatch) {
                if (!inUl) {
                    if (inOl) { processedLines.push('</ol>'); inOl = false; }
                    processedLines.push('<ul>');
                    inUl = true;
                }
                processedLines.push(`<li>${ulMatch[2]}</li>`);
            } else if (olMatch) {
                if (!inOl) {
                    if (inUl) { processedLines.push('</ul>'); inUl = false; }
                    processedLines.push('<ol>');
                    inOl = true;
                }
                processedLines.push(`<li>${olMatch[2]}</li>`);
            } else {
                if (inUl) { processedLines.push('</ul>'); inUl = false; }
                if (inOl) { processedLines.push('</ol>'); inOl = false; }
                processedLines.push(line);
            }
        }
        if (inUl) processedLines.push('</ul>');
        if (inOl) processedLines.push('</ol>');

        safe = processedLines.join('\n');

        // Step 8: Clean paragraph breaks and line breaks outside structural tags
        return safe
            .replace(/\n\n+/g, '</p><p>')
            .replace(/\n/g, '<br>')
            .replace(/<p><\/p>/g, '')
            .replace(/<br>(?=<\/?(ul|ol|li|pre|h4|h5|h6))/g, '')
            .replace(/(<\/?(ul|ol|li|pre|h4|h5|h6)[^>]*>)<br>/g, '$1');
    }

    // Sanitize pre-rendered server HTML
    function sanitizeHtml(html) {
        if (!html) return '';
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        // Remove dangerous tags
        doc.querySelectorAll('script, iframe, object, embed, style').forEach(el => el.remove());
        // Remove dangerous event attributes and validate links
        doc.querySelectorAll('*').forEach(el => {
            for (const attr of el.getAttributeNames()) {
                if (attr.toLowerCase().startsWith('on') || attr.toLowerCase() === 'srcdoc') {
                    el.removeAttribute(attr);
                }
            }
            if (el.tagName === 'A') {
                const href = (el.getAttribute('href') || '').trim();
                if (!href.startsWith('http://') && !href.startsWith('https://') && !href.startsWith('/') && !href.startsWith('#')) {
                    el.removeAttribute('href');
                } else {
                    el.setAttribute('target', '_blank');
                    el.setAttribute('rel', 'noopener noreferrer');
                }
            }
        });
        return doc.body.innerHTML;
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
                <div class="message-bubble user">${escapeHtml(text).replace(/\n/g, '<br>')}</div>
                <div class="text-muted small mt-1 text-end" style="font-size: 0.75rem;">${time || 'Just now'}</div>
            </div>
        `;
        // Insert before typing indicator
        chatMessages.insertBefore(row, typingIndicator);
        scrollToBottom();
    }

    // Append an assistant message element
    function appendAssistantMessage(text, sources, time, isFallback, convId, isPolicyFallback, htmlContent) {
        const row = document.createElement('div');
        row.className = 'message-row assistant';

        let badgeHtml = '';
        if (isPolicyFallback || (text && text.includes('Verified Policy:'))) {
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

        // Render safe markdown (using sanitized server HTML or safe client-side fallback)
        const renderedContent = htmlContent ? sanitizeHtml(htmlContent) : renderSafeMarkdown(text);

        row.innerHTML = `
            <div class="avatar-icon avatar-assistant">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <div class="message-bubble assistant">
                    ${badgeHtml}
                    <div class="markdown-content">${renderedContent}</div>
                    ${fallbackButtonHtml}
                </div>
                ${sourcesHtml}
                <div class="text-muted small mt-1" style="font-size: 0.75rem;">${time || 'Just now'}</div>
            </div>
        `;
        chatMessages.insertBefore(row, typingIndicator);
        scrollToBottom();
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

    // Keyboard handler: Enter to submit, Shift+Enter for newline
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (isSubmitting || !messageInput.value.trim()) {
                return;
            }
            if (typeof chatForm.requestSubmit === 'function') {
                chatForm.requestSubmit();
            } else {
                chatForm.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        }
    });

    // Form submit via AJAX fetch
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        dismissError();

        const message = messageInput.value.trim();
        if (!message || isSubmitting) return;

        isSubmitting = true;
        appendUserMessage(message, new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
        messageInput.value = '';
        autoResizeInput();
        setLoading(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch("{{ route('chat.send') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream, application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    message: message,
                    conversation_id: conversationIdInput.value,
                    stream: true,
                })
            });

            if (!response.ok) {
                const errData = await response.json().catch(() => ({}));
                throw new Error(errData.message || `Server error (${response.status})`);
            }

            const contentType = response.headers.get('content-type') || '';

            if (contentType.includes('text/event-stream') && response.body) {
                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let buffer = '';
                let accumulatedText = '';
                let liveRow = null;
                let contentEl = null;

                function getOrCreateLiveRow() {
                    if (liveRow) return { liveRow, contentEl };
                    typingIndicator.style.display = 'none';
                    liveRow = document.createElement('div');
                    liveRow.className = 'message-row assistant';
                    liveRow.innerHTML = `
                        <div class="avatar-icon avatar-assistant">
                            <i class="bi bi-robot"></i>
                        </div>
                        <div>
                            <div class="message-bubble assistant">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2 d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-shield-check"></i> Verified Shofol Connect Assistant
                                </span>
                                <div class="markdown-content"></div>
                            </div>
                            <div class="text-muted small mt-1" style="font-size: 0.75rem;">Generating response...</div>
                        </div>
                    `;
                    contentEl = liveRow.querySelector('.markdown-content');
                    chatMessages.insertBefore(liveRow, typingIndicator);
                    scrollToBottom();
                    return { liveRow, contentEl };
                }

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop();

                    let currentEvent = 'message';
                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (!trimmed) {
                            currentEvent = 'message';
                            continue;
                        }
                        if (trimmed.startsWith('event: ')) {
                            currentEvent = trimmed.substring(7);
                            continue;
                        }
                        if (trimmed.startsWith('data: ')) {
                            const rawData = trimmed.substring(6);
                            let dataObj;
                            try { dataObj = JSON.parse(rawData); } catch(e) { dataObj = rawData; }

                            if (currentEvent === 'token' && dataObj && dataObj.token) {
                                accumulatedText += dataObj.token;
                                const { contentEl } = getOrCreateLiveRow();
                                contentEl.innerHTML = renderSafeMarkdown(accumulatedText);
                                scrollToBottom();
                            } else if (currentEvent === 'user_message' && dataObj && dataObj.conversation_id) {
                                conversationIdInput.value = dataObj.conversation_id;
                            } else if (currentEvent === 'done' && dataObj) {
                                if (liveRow) {
                                    liveRow.remove();
                                    liveRow = null;
                                }
                                if (dataObj.conversation_id) {
                                    conversationIdInput.value = dataObj.conversation_id;
                                    const escalateBtn = document.getElementById('escalateTicketBtn');
                                    if (escalateBtn) {
                                        escalateBtn.href = "{{ route('tickets.create') }}?conversation_id=" + encodeURIComponent(dataObj.conversation_id);
                                    }
                                }
                                if (dataObj.conversation_title) {
                                    const titleSpan = document.getElementById('activeConversationTitle');
                                    if (titleSpan) {
                                        titleSpan.textContent = dataObj.conversation_title;
                                    }
                                }
                                if (dataObj.assistant_message) {
                                    appendAssistantMessage(
                                        dataObj.assistant_message.content,
                                        dataObj.assistant_message.sources,
                                        dataObj.assistant_message.created_at,
                                        dataObj.assistant_message.fallback,
                                        dataObj.conversation_id || conversationIdInput.value,
                                        dataObj.assistant_message.is_policy_fallback,
                                        dataObj.assistant_message.content_html
                                    );
                                }
                            }
                        }
                    }
                }
            } else {
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
                        data.assistant_message.is_policy_fallback,
                        data.assistant_message.content_html
                    );
                } else {
                    throw new Error('Unexpected response format from server.');
                }
            }
        } catch (err) {
            console.error(err);
            showError('The AI assistant is temporarily unavailable. Please try again in a moment or open a support ticket.');
        } finally {
            isSubmitting = false;
            setLoading(false);
            scrollToBottom();
        }
    });
</script>
@endsection

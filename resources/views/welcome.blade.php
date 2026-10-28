@extends('layouts.app-bootstrap')

@section('title', 'Shofol Connect — AI Support Assistant')

@section('styles')
<style>
    .chatgpt-landing {
        max-width: 860px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: calc(100vh - 165px);
        padding: 1.5rem 0.5rem 0.75rem 0.5rem;
    }

    /* Hero Section */
    .landing-hero {
        text-align: center;
        margin-bottom: 2rem;
    }
    .landing-logo-badge {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--theme-card-bg, #ffffff);
        border: 1px solid var(--theme-card-border, #e5e7eb);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
        margin-bottom: 1.25rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .landing-logo-badge:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 24px rgba(37, 99, 235, 0.15);
    }
    .landing-logo-img {
        width: 42px;
        height: 42px;
        object-fit: contain;
    }
    .landing-heading {
        font-size: 2.15rem;
        font-weight: 700;
        letter-spacing: -0.025em;
        margin-bottom: 0.5rem;
        color: var(--theme-body-color, #111827);
    }
    .landing-subtitle {
        font-size: 1rem;
        color: var(--theme-text-muted, #6b7280);
        max-width: 580px;
        margin: 0 auto;
        line-height: 1.5;
    }

    /* Suggestion Cards Grid */
    .prompt-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.85rem;
        margin-bottom: 1.75rem;
    }
    @media (max-width: 767.98px) {
        .prompt-grid {
            grid-template-columns: 1fr;
            gap: 0.65rem;
            margin-bottom: 1.25rem;
        }
        .landing-heading {
            font-size: 1.65rem;
        }
        .landing-hero {
            margin-bottom: 1.5rem;
        }
    }
    .prompt-card {
        background: var(--theme-card-bg, #ffffff);
        border: 1px solid var(--theme-card-border, #e5e7eb);
        border-radius: 0.875rem;
        padding: 0.95rem 1.15rem;
        text-align: left;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        color: inherit;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .prompt-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 6px 18px rgba(59, 130, 246, 0.12);
        transform: translateY(-2px);
        color: inherit;
    }
    .prompt-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .prompt-card-title {
        font-size: 0.925rem;
        font-weight: 600;
        color: var(--theme-body-color, #1f2937);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .prompt-card-icon {
        width: 28px;
        height: 28px;
        border-radius: 0.45rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }
    .prompt-card-subtitle {
        font-size: 0.8rem;
        color: var(--theme-text-muted, #6b7280);
        line-height: 1.35;
        padding-left: 2.25rem;
    }
    .prompt-card-arrow {
        opacity: 0;
        transform: translateX(-4px);
        transition: all 0.2s ease;
        color: #3b82f6;
        font-size: 0.85rem;
    }
    .prompt-card:hover .prompt-card-arrow {
        opacity: 1;
        transform: translateX(0);
    }

    /* ChatGPT-Style Rounded Message Composer */
    .composer-wrapper {
        position: relative;
        width: 100%;
    }
    .composer-container {
        background: var(--theme-card-bg, #ffffff);
        border: 1px solid var(--theme-card-border, #d1d5db);
        border-radius: 1.5rem;
        padding: 0.75rem 1.1rem;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .composer-container:focus-within {
        border-color: #2563eb;
        box-shadow: 0 8px 30px rgba(37, 99, 235, 0.14);
    }
    .composer-textarea {
        width: 100%;
        border: 0;
        outline: none;
        resize: none;
        background: transparent;
        color: var(--theme-body-color, #111827);
        font-size: 0.975rem;
        line-height: 1.5;
        max-height: 180px;
        min-height: 42px;
        padding: 0.35rem 0.2rem;
    }
    .composer-textarea::placeholder {
        color: #9ca3af;
    }
    .composer-bottom-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 0.35rem;
        padding-top: 0.35rem;
        border-top: 1px solid rgba(156, 163, 175, 0.15);
    }
    .composer-chips {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .composer-chip {
        font-size: 0.75rem;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        background: rgba(156, 163, 175, 0.12);
        color: var(--theme-text-muted, #4b5563);
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 500;
        user-select: none;
    }
    .composer-send-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 0;
        background: #2563eb;
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .composer-send-btn:hover:not(:disabled) {
        background: #1d4ed8;
        transform: scale(1.05);
    }
    .composer-send-btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
        background: #9ca3af;
    }

    /* Subtle Verification Disclaimer */
    .landing-disclaimer {
        text-align: center;
        font-size: 0.775rem;
        color: var(--theme-text-muted, #6b7280);
        margin-top: 0.75rem;
        line-height: 1.4;
    }

    /* Dark Mode Custom Rules */
    [data-bs-theme="dark"] .landing-logo-badge {
        background: #18181b;
        border-color: #27272a;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    }
    [data-bs-theme="dark"] .prompt-card {
        background: #18181b;
        border-color: #27272a;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }
    [data-bs-theme="dark"] .prompt-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 6px 18px rgba(59, 130, 246, 0.25);
    }
    [data-bs-theme="dark"] .composer-container {
        background: #18181b;
        border-color: #27272a;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35);
    }
    [data-bs-theme="dark"] .composer-container:focus-within {
        border-color: #3b82f6;
        box-shadow: 0 8px 30px rgba(59, 130, 246, 0.25);
    }
    [data-bs-theme="dark"] .composer-chip {
        background: rgba(255, 255, 255, 0.08);
        color: #9ca3af;
    }
</style>
@endsection

@section('content')
<div class="chatgpt-landing">
    <!-- Center Welcoming Hero -->
    <div class="landing-hero">
        <div class="landing-logo-badge shadow-sm">
            <img src="{{ asset('images/shofol-connect-logo.png') }}" alt="Shofol Connect" class="landing-logo-img">
        </div>
        <h1 class="landing-heading">How can I help you today?</h1>
        <p class="landing-subtitle">
            Ask anything about company HRM policies, payroll calculations, CRM sales pipelines, or accounting and invoicing.
        </p>
    </div>

    <!-- 4 Example Prompt Suggestions -->
    <div class="prompt-grid">
        <!-- Leave Policy Card -->
        <button type="button"
                class="prompt-card prompt-suggestion-btn border-0"
                data-prompt="What is the leave policy for annual, casual, and sick leaves at Shofol Connect?">
            <div class="prompt-card-header">
                <span class="prompt-card-title">
                    <span class="prompt-card-icon bg-primary-subtle text-primary">
                        <i class="bi bi-calendar2-check"></i>
                    </span>
                    Leave policy
                </span>
                <i class="bi bi-arrow-right prompt-card-arrow"></i>
            </div>
            <div class="prompt-card-subtitle">
                Entitlements, leave approvals, casual and medical leave guidelines
            </div>
        </button>

        <!-- Payroll and Salary Card -->
        <button type="button"
                class="prompt-card prompt-suggestion-btn border-0"
                data-prompt="How does monthly salary disbursement, allowances, and tax deductions work?">
            <div class="prompt-card-header">
                <span class="prompt-card-title">
                    <span class="prompt-card-icon bg-success-subtle text-success">
                        <i class="bi bi-cash-stack"></i>
                    </span>
                    Payroll and salary
                </span>
                <i class="bi bi-arrow-right prompt-card-arrow"></i>
            </div>
            <div class="prompt-card-subtitle">
                Pay schedules, provident fund, allowances, and payslip generation
            </div>
        </button>

        <!-- CRM and Sales Card -->
        <button type="button"
                class="prompt-card prompt-suggestion-btn border-0"
                data-prompt="How do I track client leads, opportunities, and sales pipeline stages in Shofol?">
            <div class="prompt-card-header">
                <span class="prompt-card-title">
                    <span class="prompt-card-icon bg-warning-subtle text-warning">
                        <i class="bi bi-briefcase"></i>
                    </span>
                    CRM and sales
                </span>
                <i class="bi bi-arrow-right prompt-card-arrow"></i>
            </div>
            <div class="prompt-card-subtitle">
                Lead acquisition, customer interaction logs, and pipeline conversion
            </div>
        </button>

        <!-- Accounting and Invoicing Card -->
        <button type="button"
                class="prompt-card prompt-suggestion-btn border-0"
                data-prompt="How do I create tax invoices and reconcile customer and vendor payments?">
            <div class="prompt-card-header">
                <span class="prompt-card-title">
                    <span class="prompt-card-icon bg-info-subtle text-info">
                        <i class="bi bi-receipt"></i>
                    </span>
                    Accounting and invoicing
                </span>
                <i class="bi bi-arrow-right prompt-card-arrow"></i>
            </div>
            <div class="prompt-card-subtitle">
                Bill creation, payment matching, expense tracking, and VAT records
            </div>
        </button>
    </div>

    <!-- Large Rounded ChatGPT-Style Message Composer -->
    <div class="composer-wrapper">
        <form id="homepageComposerForm"
              method="POST"
              action="{{ route('chat.send') }}"
              data-authenticated="{{ Auth::check() ? '1' : '0' }}">
            @csrf
            <div class="composer-container">
                <textarea id="homepageComposerInput"
                          name="message"
                          class="composer-textarea"
                          rows="1"
                          placeholder="Message Shofol Connect AI Assistant..."
                          aria-label="Message Shofol Connect AI Assistant"
                          required
                          autocomplete="off"
                          spellcheck="true"></textarea>

                <div class="composer-bottom-bar">
                    <div class="composer-chips">
                        <span class="composer-chip">
                            <i class="bi bi-shield-check text-success"></i>
                            <span>RAG Grounded</span>
                        </span>
                        <span class="composer-chip d-none d-sm-inline-flex">
                            <i class="bi bi-lightning-charge-fill text-warning"></i>
                            <span>Dual AI (Gemini / Ollama)</span>
                        </span>
                    </div>

                    <button type="submit"
                            id="homepageSendBtn"
                            class="composer-send-btn"
                            title="Send message"
                            aria-label="Send message"
                            disabled>
                        <i class="bi bi-arrow-up-short fs-4" id="sendBtnIcon"></i>
                        <span class="spinner-border spinner-border-sm d-none" id="sendBtnSpinner" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Subtle AI Verification Disclaimer -->
        <div class="landing-disclaimer">
            <i class="bi bi-info-circle me-1"></i>
            <span>Shofol Connect AI Assistant can make mistakes. Please verify important policies and financial details with HR or Finance.</span>
        </div>
    </div>
</div>

<!-- Guest Authentication Modal (Shown when guests try to submit or click prompt suggestions) -->
<div class="modal fade" id="guestAuthModal" tabindex="-1" aria-labelledby="guestAuthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ asset('images/shofol-connect-logo.png') }}" alt="Shofol Connect" width="30" height="30" class="rounded">
                    <h5 class="modal-title fw-bold" id="guestAuthModalLabel">Sign in to Shofol Connect</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-secondary mb-3">
                    To ask questions, receive verified answers from the company knowledge base, and save your chat history, please sign in or register for a Shofol Connect account.
                </p>

                <div id="guestPromptPreview" class="p-3 rounded-3 bg-body-tertiary border mb-3 small d-none">
                    <div class="fw-semibold text-muted mb-1">Your query:</div>
                    <div id="guestPromptPreviewText" class="text-body fst-italic"></div>
                </div>

                <div class="d-grid gap-2">
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Log In</span>
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-lg d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-person-plus"></i>
                            <span>Register New Account</span>
                        </a>
                    @endif
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 text-center justify-content-center">
                <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none" data-bs-dismiss="modal">
                    Continue browsing as guest
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('homepageComposerForm');
    const input = document.getElementById('homepageComposerInput');
    const sendBtn = document.getElementById('homepageSendBtn');
    const sendIcon = document.getElementById('sendBtnIcon');
    const sendSpinner = document.getElementById('sendBtnSpinner');
    const promptButtons = document.querySelectorAll('.prompt-suggestion-btn');
    const guestModalEl = document.getElementById('guestAuthModal');
    const guestModal = guestModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(guestModalEl) : null;
    const guestPromptPreview = document.getElementById('guestPromptPreview');
    const guestPromptPreviewText = document.getElementById('guestPromptPreviewText');
    const isAuthenticated = form && form.getAttribute('data-authenticated') === '1';

    // Auto-resize textarea to fit content up to max-height
    function autoResize() {
        if (!input) return;
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 180) + 'px';
        const hasText = input.value.trim().length > 0;
        if (sendBtn) {
            sendBtn.disabled = !hasText;
        }
    }

    if (input) {
        input.addEventListener('input', autoResize);

        // Submit on Enter (unless Shift is held)
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (!sendBtn.disabled) {
                    form.dispatchEvent(new Event('submit', { cancelable: true }));
                }
            }
        });

        // Focus input
        input.focus();
    }

    // Handle suggestion prompt clicks
    promptButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const promptText = this.getAttribute('data-prompt');
            if (input && promptText) {
                input.value = promptText;
                autoResize();
                input.focus();

                // If guest, show preview and prompt sign in
                if (!isAuthenticated && guestModal) {
                    if (guestPromptPreview && guestPromptPreviewText) {
                        guestPromptPreviewText.textContent = promptText;
                        guestPromptPreview.classList.remove('d-none');
                    }
                    guestModal.show();
                }
            }
        });
    });

    // Handle form submission
    if (form) {
        form.addEventListener('submit', function (e) {
            const text = input ? input.value.trim() : '';
            if (!text) {
                e.preventDefault();
                return;
            }

            if (!isAuthenticated) {
                e.preventDefault();
                if (guestPromptPreview && guestPromptPreviewText) {
                    guestPromptPreviewText.textContent = text;
                    guestPromptPreview.classList.remove('d-none');
                }
                if (guestModal) {
                    guestModal.show();
                } else {
                    window.location.href = "{{ route('login') }}";
                }
                return;
            }

            // Authenticated: show loading spinner and allow normal submit to chat.send
            if (sendBtn && sendIcon && sendSpinner) {
                sendBtn.disabled = true;
                sendIcon.classList.add('d-none');
                sendSpinner.classList.remove('d-none');
            }
        });
    }

    // Check query string for auto-populating topic if clicked from sidebar
    const urlParams = new URLSearchParams(window.location.search);
    const topic = urlParams.get('topic') || urlParams.get('prompt');
    if (topic && input) {
        const topicMap = {
            'hrm': 'What is the leave policy for annual, casual, and sick leaves at Shofol Connect?',
            'leave-policy': 'What is the leave policy for annual, casual, and sick leaves at Shofol Connect?',
            'payroll': 'How does monthly salary disbursement, allowances, and tax deductions work?',
            'payroll-salary': 'How does monthly salary disbursement, allowances, and tax deductions work?',
            'crm': 'How do I track client leads, opportunities, and sales pipeline stages in Shofol?',
            'crm-sales': 'How do I track client leads, opportunities, and sales pipeline stages in Shofol?',
            'accounting': 'How do I create tax invoices and reconcile customer and vendor payments?',
            'accounting-invoicing': 'How do I create tax invoices and reconcile customer and vendor payments?'
        };
        if (topicMap[topic]) {
            input.value = topicMap[topic];
            autoResize();
        }
    }
});
</script>
@endsection

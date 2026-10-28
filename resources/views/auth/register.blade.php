<x-guest-layout :hide-logo="true" card-class="w-full sm:max-w-lg my-4 p-4 sm:p-5 bg-white dark:bg-zinc-900 shadow-xl rounded-4 border border-gray-200/80 dark:border-zinc-800 transition-colors duration-200">
    <style>
        .auth-logo-badge {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .auth-logo-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.15);
        }
        .auth-logo-img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }
        .auth-heading {
            font-size: 1.65rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 0.35rem;
            color: #111827;
        }
        .auth-subtitle {
            font-size: 0.925rem;
            color: #6b7280;
            margin-bottom: 0;
            line-height: 1.4;
        }
        .auth-input-group .input-group-text {
            background-color: #f8fafc;
            border-color: #d1d5db;
            color: #6b7280;
            border-radius: 0.5rem 0 0 0.5rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .auth-input-group .form-control {
            border-color: #d1d5db;
            font-size: 0.95rem;
            padding: 0.6rem 0.85rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .auth-input-group .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.12);
        }
        .auth-input-group:focus-within .input-group-text {
            border-color: #2563eb;
        }
        .auth-toggle-btn {
            border-color: #d1d5db;
            background-color: #f8fafc;
            color: #6b7280;
            border-radius: 0 0.5rem 0.5rem 0;
            padding: 0 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        .auth-toggle-btn:hover {
            background-color: #e2e8f0;
            color: #1f2937;
        }
        .auth-submit-btn {
            background-color: #2563eb;
            border: 0;
            padding: 0.7rem 1.25rem;
            font-size: 0.975rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            border-radius: 0.625rem;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            transition: all 0.2s ease;
        }
        .auth-submit-btn:hover:not(:disabled) {
            background-color: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }
        .auth-submit-btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
        }

        /* Dark Theme Styling */
        [data-bs-theme="dark"] .auth-logo-badge,
        .dark .auth-logo-badge {
            background: #18181b;
            border-color: #27272a;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
        }
        [data-bs-theme="dark"] .auth-heading,
        .dark .auth-heading {
            color: #f3f4f6;
        }
        [data-bs-theme="dark"] .auth-subtitle,
        .dark .auth-subtitle {
            color: #9ca3af;
        }
        [data-bs-theme="dark"] .auth-input-group .input-group-text,
        .dark .auth-input-group .input-group-text {
            background-color: #27272a;
            border-color: #3f3f46;
            color: #9ca3af;
        }
        [data-bs-theme="dark"] .auth-input-group .form-control,
        .dark .auth-input-group .form-control {
            background-color: #18181b;
            border-color: #3f3f46;
            color: #f3f4f6;
        }
        [data-bs-theme="dark"] .auth-input-group .form-control:focus,
        .dark .auth-input-group .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.2);
        }
        [data-bs-theme="dark"] .auth-input-group:focus-within .input-group-text,
        .dark .auth-input-group:focus-within .input-group-text {
            border-color: #3b82f6;
        }
        [data-bs-theme="dark"] .auth-toggle-btn,
        .dark .auth-toggle-btn {
            background-color: #27272a;
            border-color: #3f3f46;
            color: #9ca3af;
        }
        [data-bs-theme="dark"] .auth-toggle-btn:hover,
        .dark .auth-toggle-btn:hover {
            background-color: #3f3f46;
            color: #f3f4f6;
        }
    </style>

    <!-- Centered Header with Single Shofol Connect Logo -->
    <div class="text-center mb-4">
        <a href="/" class="d-inline-flex align-items-center justify-content-center text-decoration-none mb-3" title="Return to Shofol Connect Home">
            <div class="auth-logo-badge shadow-sm">
                <img src="{{ asset('images/shofol-connect-logo.png') }}" alt="Shofol Connect" class="auth-logo-img">
            </div>
        </a>
        <h1 class="auth-heading">Create your account</h1>
        <p class="auth-subtitle">Get started with Shofol Connect AI Support</p>
    </div>

    <!-- Registration Form -->
    <form method="POST" action="{{ route('register') }}" id="registerForm" novalidate>
        @csrf

        <!-- Full Name -->
        <div class="mb-3">
            <label for="name" class="form-label fw-semibold small text-secondary dark:text-gray-300">
                Full Name <span class="text-danger">*</span>
            </label>
            <div class="input-group auth-input-group">
                <span class="input-group-text border-end-0">
                    <i class="bi bi-person"></i>
                </span>
                <input type="text"
                       id="name"
                       name="name"
                       class="form-control border-start-0 ps-1 @error('name') is-invalid @enderror"
                       placeholder="e.g. Anwar Parvez"
                       value="{{ old('name') }}"
                       required
                       autofocus
                       autocomplete="name">
            </div>
            @error('name')
                <div class="text-danger small mt-1 d-flex align-items-center gap-1" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold small text-secondary dark:text-gray-300">
                Email Address <span class="text-danger">*</span>
            </label>
            <div class="input-group auth-input-group">
                <span class="input-group-text border-end-0">
                    <i class="bi bi-envelope"></i>
                </span>
                <input type="email"
                       id="email"
                       name="email"
                       class="form-control border-start-0 ps-1 @error('email') is-invalid @enderror"
                       placeholder="name@company.com"
                       value="{{ old('email') }}"
                       required
                       autocomplete="username">
            </div>
            @error('email')
                <div class="text-danger small mt-1 d-flex align-items-center gap-1" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label fw-semibold small text-secondary dark:text-gray-300">
                Password <span class="text-danger">*</span>
            </label>
            <div class="input-group auth-input-group">
                <span class="input-group-text border-end-0">
                    <i class="bi bi-lock"></i>
                </span>
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control border-start-0 border-end-0 ps-1 @error('password') is-invalid @enderror"
                       placeholder="At least 8 characters"
                       required
                       autocomplete="new-password">
                <button type="button"
                        id="togglePasswordBtn"
                        class="btn auth-toggle-btn border-start-0"
                        title="Show password"
                        aria-label="Show password">
                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                </button>
            </div>
            <div class="text-muted dark:text-gray-400 mt-1" style="font-size: 0.775rem;">
                Use 8 or more characters with a mix of letters, numbers & symbols.
            </div>
            @error('password')
                <div class="text-danger small mt-1 d-flex align-items-center gap-1" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-semibold small text-secondary dark:text-gray-300">
                Confirm Password <span class="text-danger">*</span>
            </label>
            <div class="input-group auth-input-group">
                <span class="input-group-text border-end-0">
                    <i class="bi bi-shield-check"></i>
                </span>
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       class="form-control border-start-0 border-end-0 ps-1 @error('password_confirmation') is-invalid @enderror"
                       placeholder="Re-enter your password"
                       required
                       autocomplete="new-password">
                <button type="button"
                        id="togglePasswordConfirmBtn"
                        class="btn auth-toggle-btn border-start-0"
                        title="Show password"
                        aria-label="Show password">
                    <i class="bi bi-eye" id="togglePasswordConfirmIcon"></i>
                </button>
            </div>
            @error('password_confirmation')
                <div class="text-danger small mt-1 d-flex align-items-center gap-1" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <!-- Primary Submit Button with Loading State -->
        <button type="submit"
                id="registerSubmitBtn"
                class="btn btn-primary auth-submit-btn w-100 d-flex align-items-center justify-content-center gap-2">
            <span id="registerBtnText">Create account</span>
            <i class="bi bi-arrow-right fs-5" id="registerBtnIcon"></i>
            <span class="spinner-border spinner-border-sm d-none" id="registerBtnSpinner" role="status" aria-hidden="true"></span>
        </button>

        <!-- Already registered link -->
        <div class="text-center mt-3 pt-3 border-top border-gray-200 dark:border-zinc-800">
            <span class="text-secondary dark:text-gray-400 small">Already have an account?</span>
            <a href="{{ route('login') }}" class="text-primary fw-semibold small text-decoration-none ms-1 hover:underline">
                Log in
            </a>
        </div>
    </form>

    <!-- Small Footer Note -->
    <div class="text-center mt-4 pt-1">
        <span class="text-muted dark:text-gray-400 small d-inline-flex align-items-center gap-1" style="font-size: 0.775rem;">
            <i class="bi bi-info-circle"></i>
            <span>AI responses may require verification.</span>
        </span>
    </div>

    <!-- Password Visibility Toggle & Loading State Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Helper for password visibility toggling
            function setupPasswordToggle(btnId, inputId, iconId) {
                const btn = document.getElementById(btnId);
                const input = document.getElementById(inputId);
                const icon = document.getElementById(iconId);
                if (!btn || !input || !icon) return;

                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    icon.classList.toggle('bi-eye', !isPassword);
                    icon.classList.toggle('bi-eye-slash', isPassword);
                    const title = isPassword ? 'Hide password' : 'Show password';
                    btn.setAttribute('title', title);
                    btn.setAttribute('aria-label', title);
                    input.focus();
                });
            }

            setupPasswordToggle('togglePasswordBtn', 'password', 'togglePasswordIcon');
            setupPasswordToggle('togglePasswordConfirmBtn', 'password_confirmation', 'togglePasswordConfirmIcon');

            // Form submission loading state
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('registerSubmitBtn');
            const btnText = document.getElementById('registerBtnText');
            const btnIcon = document.getElementById('registerBtnIcon');
            const btnSpinner = document.getElementById('registerBtnSpinner');

            if (form && submitBtn) {
                form.addEventListener('submit', function () {
                    // Check HTML5 validity before triggering loading UI
                    if (form.checkValidity && !form.checkValidity()) {
                        return;
                    }
                    submitBtn.disabled = true;
                    if (btnText) btnText.textContent = 'Creating account...';
                    if (btnIcon) btnIcon.classList.add('d-none');
                    if (btnSpinner) btnSpinner.classList.remove('d-none');
                });
            }
        });
    </script>
</x-guest-layout>

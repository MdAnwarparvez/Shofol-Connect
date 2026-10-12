<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Shofol Connect — AI Support Assistant')</title>

    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --brand-primary: #0d6efd;
            --brand-dark: #1e293b;
        }
        body {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
        }
        .main-content {
            flex: 1 0 auto;
        }
        .navbar-brand-badge {
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        .footer {
            flex-shrink: 0;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
        }
    </style>
    @yield('styles')
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ Auth::check() && Auth::user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}">
                <i class="bi bi-robot text-primary fs-4"></i>
                <span>Shofol Connect</span>
                <span class="badge bg-secondary navbar-brand-badge text-uppercase">MVP</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                @auth
                    <!-- Left Navigation Links -->
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2 me-1"></i> User Portal
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('chat.*') ? 'active fw-semibold' : '' }}" href="{{ route('chat.index') }}">
                                <i class="bi bi-robot me-1"></i> AI Assistant
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('conversations.*') ? 'active fw-semibold' : '' }}" href="{{ route('conversations.index') }}">
                                <i class="bi bi-clock-history me-1"></i> History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tickets.*') ? 'active fw-semibold' : '' }}" href="{{ route('tickets.index') }}">
                                <i class="bi bi-ticket-detailed me-1"></i> Support Tickets
                            </a>
                        </li>
                        @if(Auth::user()->isAdmin())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('admin.dashboard') }}">
                                    <i class="bi bi-shield-lock me-1"></i> Admin Panel
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.knowledge-articles.*') ? 'active fw-semibold' : '' }}" href="{{ route('admin.knowledge-articles.index') }}">
                                    <i class="bi bi-journal-bookmark me-1"></i> Knowledge Base
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.modules.*') ? 'active fw-semibold' : '' }}" href="{{ route('admin.modules.index') }}">
                                    <i class="bi bi-grid-3x3-gap me-1"></i> Modules
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active fw-semibold' : '' }}" href="{{ route('admin.categories.index') }}">
                                    <i class="bi bi-tags me-1"></i> Categories
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.faqs.*') ? 'active fw-semibold' : '' }}" href="{{ route('admin.faqs.index') }}">
                                    <i class="bi bi-question-circle me-1"></i> FAQs
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.tickets.*') ? 'active fw-semibold' : '' }}" href="{{ route('admin.tickets.index') }}">
                                    <i class="bi bi-ticket-perforated-fill me-1"></i> Manage Tickets
                                </a>
                            </li>
                        @endif
                    </ul>

                    <!-- Right Navigation User Menu -->
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                        <li class="nav-item text-light d-flex align-items-center gap-2">
                            <span class="text-white-50 small">Signed in as:</span>
                            <span class="fw-semibold text-white">{{ Auth::user()->name }}</span>
                            @if(Auth::user()->isAdmin())
                                <span class="badge bg-danger">Admin</span>
                            @else
                                <span class="badge bg-primary">User</span>
                            @endif
                        </li>

                        <li class="nav-item ms-lg-2">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-light btn-sm d-flex align-items-center gap-1">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Log Out</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                @else
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Log In</a>
                        </li>
                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">Register</a>
                            </li>
                        @endif
                    </ul>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="main-content py-4">
        <div class="container">
            <!-- Flash Message Alerts -->
            @if (session('status'))
                <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i> {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer py-3 text-center text-muted small">
        <div class="container">
            <span>&copy; {{ date('Y') }} <strong>Shofol Connect</strong> — AI Support Assistant MVP. All rights reserved.</span>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @yield('scripts')
</body>
</html>

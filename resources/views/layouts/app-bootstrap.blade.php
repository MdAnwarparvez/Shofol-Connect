<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Shofol Connect — AI Support Assistant')</title>

    <!-- Anti-FOUC Theme Script (executes before paint) -->
    <script id="theme-init">
        (function () {
            try {
                var theme = localStorage.getItem('shofol_theme') || 'light';
                document.documentElement.setAttribute('data-bs-theme', theme);
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-bs-theme', 'light');
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root,
        [data-bs-theme="light"] {
            --brand-primary: #0d6efd;
            --brand-dark: #1e293b;
            --sidebar-bg: #171717;
            --sidebar-hover: rgba(255, 255, 255, 0.08);
            --sidebar-active: rgba(255, 255, 255, 0.16);
            --sidebar-border: rgba(255, 255, 255, 0.1);
            --sidebar-width: 260px;
            --sidebar-min-width: 200px;
            --sidebar-max-width: 400px;

            /* Theme Tokens - Light */
            --theme-body-bg: #f8fafc;
            --theme-body-color: #1e293b;
            --theme-workspace-bg: #f8fafc;
            --theme-topbar-bg: #ffffff;
            --theme-topbar-border: #e2e8f0;
            --theme-card-bg: #ffffff;
            --theme-card-border: #e2e8f0;
            --theme-footer-bg: #ffffff;
            --theme-footer-border: #e2e8f0;
            --theme-text-muted: #64748b;
            --theme-text-heading: #0f172a;
            --theme-input-bg: #ffffff;
            --theme-input-border: #cbd5e1;
            --theme-input-color: #1e293b;
            --theme-chat-user-bg: #0d6efd;
            --theme-chat-user-color: #ffffff;
            --theme-chat-assistant-bg: #ffffff;
            --theme-chat-assistant-color: #1e293b;
            --theme-chat-assistant-border: #e2e8f0;
            --theme-chat-messages-bg: #f8fafc;
            --theme-sources-bg: #f1f5f9;
            --theme-sources-color: #475569;
            --theme-code-bg: #f1f5f9;
            --theme-code-color: #0f172a;
        }

        [data-bs-theme="dark"] {
            --brand-primary: #3b82f6;
            --brand-dark: #0f172a;
            --sidebar-bg: #0f172a;
            --sidebar-hover: rgba(255, 255, 255, 0.08);
            --sidebar-active: rgba(255, 255, 255, 0.16);
            --sidebar-border: rgba(255, 255, 255, 0.12);

            /* Theme Tokens - Dark */
            --theme-body-bg: #0b0f19;
            --theme-body-color: #e2e8f0;
            --theme-workspace-bg: #0b0f19;
            --theme-topbar-bg: #111827;
            --theme-topbar-border: #1f2937;
            --theme-card-bg: #111827;
            --theme-card-border: #1f2937;
            --theme-footer-bg: #111827;
            --theme-footer-border: #1f2937;
            --theme-text-muted: #9ca3af;
            --theme-text-heading: #f9fafb;
            --theme-input-bg: #0b0f19;
            --theme-input-border: #374151;
            --theme-input-color: #f9fafb;
            --theme-chat-user-bg: #2563eb;
            --theme-chat-user-color: #ffffff;
            --theme-chat-assistant-bg: #111827;
            --theme-chat-assistant-color: #f9fafb;
            --theme-chat-assistant-border: #1f2937;
            --theme-chat-messages-bg: #070a10;
            --theme-sources-bg: #1f2937;
            --theme-sources-color: #9ca3af;
            --theme-code-bg: #1f2937;
            --theme-code-color: #93c5fd;
        }

        body {
            background-color: var(--theme-body-bg);
            color: var(--theme-body-color);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        /* App Shell (ChatGPT layout) */
        .app-shell {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            position: relative;
        }

        /* ChatGPT Sidebar */
        .chatgpt-sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--sidebar-bg);
            color: #ececf1;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            z-index: 1040;
            position: relative;
            container-type: inline-size;
            container-name: sidebar;
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                        min-width 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                        max-width 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                        opacity 0.2s ease;
            border-right: 1px solid var(--sidebar-border);
            overflow: hidden;
        }

        /* Sidebar Draggable Resize Handle */
        .sidebar-resizer {
            position: absolute;
            top: 0;
            right: 0;
            width: 8px;
            height: 100%;
            cursor: col-resize;
            z-index: 1045;
            background: transparent;
            transition: background 0.15s ease;
        }
        .sidebar-resizer::after {
            content: '';
            position: absolute;
            top: 0;
            right: -3px;
            width: 14px;
            height: 100%;
        }
        .sidebar-resizer:hover,
        .app-shell.is-resizing .sidebar-resizer {
            background: rgba(13, 110, 253, 0.4);
        }

        /* Resizing state: prevent text selection and transition lag during drag */
        .app-shell.is-resizing,
        .app-shell.is-resizing * {
            user-select: none !important;
            cursor: col-resize !important;
        }
        .app-shell.is-resizing .chatgpt-sidebar,
        .app-shell.is-resizing .main-workspace {
            transition: none !important;
        }

        .sidebar-header {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--sidebar-border);
            flex-shrink: 0;
        }

        .navbar-brand {
            transition: opacity 0.2s ease-in-out;
            text-decoration: none;
            padding: 0;
            display: inline-flex;
            align-items: center;
        }
        .navbar-brand:hover {
            opacity: 0.9;
        }
        .shofol-navbar-logo,
        .appifly-navbar-logo {
            height: 30px;
            width: auto;
            max-height: 30px;
            aspect-ratio: 640 / 533;
            object-fit: contain;
            display: inline-block;
            flex-shrink: 0;
            transition: transform 0.2s ease-in-out;
        }
        .brand-title {
            font-size: 1.1rem;
            letter-spacing: -0.01em;
            white-space: nowrap;
        }
        .navbar-brand:hover .shofol-navbar-logo,
        .navbar-brand:hover .appifly-navbar-logo {
            transform: scale(1.03);
        }

        /* Collapsed mode and limited space handling: show only logo when space is limited */
        @container sidebar (max-width: 220px) {
            .sidebar-header .brand-title {
                display: none !important;
            }
        }
        /* Collapsed mode topbar brand logo (reopens sidebar on click) */
        .collapsed-topbar-brand {
            display: none;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            padding: 2px;
            margin: 0;
            cursor: pointer;
            text-decoration: none;
            line-height: 1;
            transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
            flex-shrink: 0;
        }
        .collapsed-topbar-brand:hover {
            opacity: 0.85;
            transform: scale(1.08);
        }
        @media (min-width: 992px) {
            .app-shell.sidebar-collapsed .collapsed-topbar-brand {
                display: inline-flex;
            }
        }
        @media (max-width: 991.98px) {
            .collapsed-topbar-brand {
                display: inline-flex;
            }
            .app-shell.sidebar-mobile-open .collapsed-topbar-brand {
                display: none;
            }
        }
        .shofol-topbar-logo {
            height: 28px;
            width: auto;
            max-height: 28px;
            aspect-ratio: 640 / 533;
            object-fit: contain;
            flex-shrink: 0;
            display: block;
        }

        /* Compact Sidebar Toggle Button in Topbar */
        .sidebar-toggle-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            flex-shrink: 0;
        }
        .sidebar-toggle-btn:hover {
            transform: scale(1.05);
        }
        .sidebar-toggle-btn i {
            font-size: 1rem;
            line-height: 1;
        }

        /* New Chat CTA Button */
        .sidebar-new-chat-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--sidebar-border);
            color: #ffffff;
            border-radius: 0.5rem;
            padding: 0.55rem 0.85rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .sidebar-new-chat-btn:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.25);
        }
        .sidebar-new-chat-btn.active-chat {
            background: rgba(13, 110, 253, 0.2);
            border-color: rgba(13, 110, 253, 0.5);
            color: #ffffff;
        }

        /* Scrollable Navigation */
        .sidebar-nav-scroll {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0.5rem 0.75rem;
        }
        .sidebar-nav-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-nav-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .sidebar-section-title {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #9ca3af;
            padding: 0.75rem 0.65rem 0.35rem 0.65rem;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.55rem 0.75rem;
            border-radius: 0.5rem;
            color: #d1d5db;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 2px;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .sidebar-nav-link i {
            font-size: 1.05rem;
            width: 1.25rem;
            text-align: center;
            color: #9ca3af;
            transition: color 0.15s ease;
        }
        .sidebar-nav-link:hover {
            background-color: var(--sidebar-hover);
            color: #ffffff;
        }
        .sidebar-nav-link:hover i {
            color: #ffffff;
        }
        .sidebar-nav-link.active {
            background-color: var(--sidebar-active);
            color: #ffffff;
            font-weight: 600;
            box-shadow: inset 3px 0 0 var(--brand-primary);
        }
        .sidebar-nav-link.active i {
            color: var(--brand-primary);
        }

        /* Account Section at bottom of Sidebar */
        .sidebar-account-footer {
            flex-shrink: 0;
            border-top: 1px solid var(--sidebar-border);
            padding: 0.85rem 0.95rem;
            background-color: #121212;
        }
        .user-avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #262626;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        /* Main Workspace on Right */
        .main-workspace {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            background-color: var(--theme-workspace-bg);
            color: var(--theme-body-color);
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        /* Workspace Topbar */
        .workspace-topbar {
            height: 54px;
            background: var(--theme-topbar-bg);
            border-bottom: 1px solid var(--theme-topbar-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.25rem;
            flex-shrink: 0;
            z-index: 1020;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        /* Workspace Body */
        .workspace-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
        }

        .main-content-inner {
            flex: 1 0 auto;
        }

        /* Desktop Collapsed State */
        @media (min-width: 992px) {
            .app-shell.sidebar-collapsed .chatgpt-sidebar {
                width: 0 !important;
                min-width: 0 !important;
                max-width: 0 !important;
                border-right: none;
                visibility: hidden;
                opacity: 0;
                pointer-events: none;
            }
            .app-shell.sidebar-collapsed .sidebar-resizer {
                display: none !important;
                pointer-events: none !important;
            }
            .app-shell.sidebar-collapsed .main-workspace {
                width: 100vw;
                max-width: 100vw;
            }
        }

        /* Mobile Drawer State */
        @media (max-width: 991.98px) {
            .chatgpt-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 280px !important;
                min-width: 280px !important;
                max-width: 85vw !important;
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 0 25px rgba(0, 0, 0, 0.5);
                z-index: 1050;
                visibility: visible !important;
                opacity: 1 !important;
                pointer-events: auto !important;
            }
            .sidebar-resizer {
                display: none !important;
                pointer-events: none !important;
            }
            .sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1045;
                opacity: 0;
                transition: opacity 0.25s ease;
            }
            .app-shell.sidebar-mobile-open .chatgpt-sidebar {
                transform: translateX(0) !important;
            }
            .app-shell.sidebar-mobile-open .sidebar-backdrop {
                display: block;
                opacity: 1;
            }
        }

        .footer-credit-link {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.15s ease-in-out;
        }
        .footer-credit-link:hover {
            color: var(--brand-primary);
            text-decoration: underline;
        }
        .footer {
            flex-shrink: 0;
            background: var(--theme-footer-bg);
            border-top: 1px solid var(--theme-footer-border);
            color: var(--theme-text-muted);
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        /* COMPLETE DARK THEME COMPONENT OVERRIDES */
        [data-bs-theme="dark"] .bg-white {
            background-color: var(--theme-card-bg) !important;
            color: var(--theme-body-color) !important;
            border-color: var(--theme-card-border) !important;
        }

        [data-bs-theme="dark"] .bg-light {
            background-color: #1f2937 !important;
            color: var(--theme-body-color) !important;
            border-color: var(--theme-card-border) !important;
        }

        [data-bs-theme="dark"] .card {
            background-color: var(--theme-card-bg);
            border-color: var(--theme-card-border) !important;
            color: var(--theme-body-color);
        }

        [data-bs-theme="dark"] .card-header,
        [data-bs-theme="dark"] .card-footer {
            background-color: var(--theme-card-bg) !important;
            border-color: var(--theme-card-border) !important;
            color: var(--theme-body-color) !important;
        }

        [data-bs-theme="dark"] .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--theme-body-color);
            --bs-table-border-color: var(--theme-card-border);
            --bs-table-striped-bg: rgba(255, 255, 255, 0.025);
            --bs-table-hover-bg: rgba(255, 255, 255, 0.04);
            color: var(--theme-body-color);
        }

        [data-bs-theme="dark"] .table-light,
        [data-bs-theme="dark"] thead.table-light th {
            background-color: #162032 !important;
            color: #e2e8f0 !important;
            border-color: var(--theme-card-border) !important;
        }

        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select {
            background-color: var(--theme-input-bg) !important;
            border-color: var(--theme-input-border) !important;
            color: var(--theme-input-color) !important;
        }

        [data-bs-theme="dark"] .form-control:focus,
        [data-bs-theme="dark"] .form-select:focus {
            background-color: var(--theme-input-bg) !important;
            border-color: var(--brand-primary) !important;
            color: var(--theme-input-color) !important;
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }

        [data-bs-theme="dark"] .form-control::placeholder {
            color: var(--theme-text-muted) !important;
        }

        [data-bs-theme="dark"] .input-group-text {
            background-color: #1f2937 !important;
            border-color: var(--theme-input-border) !important;
            color: var(--theme-body-color) !important;
        }

        [data-bs-theme="dark"] .modal-content {
            background-color: var(--theme-card-bg) !important;
            border-color: var(--theme-card-border) !important;
            color: var(--theme-body-color) !important;
        }

        [data-bs-theme="dark"] .modal-header,
        [data-bs-theme="dark"] .modal-footer {
            border-color: var(--theme-card-border) !important;
        }

        [data-bs-theme="dark"] .dropdown-menu {
            background-color: var(--theme-card-bg) !important;
            border-color: var(--theme-card-border) !important;
        }

        [data-bs-theme="dark"] .dropdown-item {
            color: var(--theme-body-color) !important;
        }

        [data-bs-theme="dark"] .dropdown-item:hover,
        [data-bs-theme="dark"] .dropdown-item:focus {
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .list-group-item {
            background-color: var(--theme-card-bg) !important;
            border-color: var(--theme-card-border) !important;
            color: var(--theme-body-color) !important;
        }

        [data-bs-theme="dark"] .page-link {
            background-color: var(--theme-card-bg) !important;
            border-color: var(--theme-card-border) !important;
            color: var(--theme-body-color) !important;
        }

        [data-bs-theme="dark"] .page-item.active .page-link {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .page-item.disabled .page-link {
            background-color: #1f2937 !important;
            border-color: var(--theme-card-border) !important;
            color: #6b7280 !important;
        }

        [data-bs-theme="dark"] .border,
        [data-bs-theme="dark"] .border-top,
        [data-bs-theme="dark"] .border-bottom,
        [data-bs-theme="dark"] .border-start,
        [data-bs-theme="dark"] .border-end {
            border-color: var(--theme-card-border) !important;
        }

        [data-bs-theme="dark"] .text-dark {
            color: var(--theme-text-heading) !important;
        }

        [data-bs-theme="dark"] .text-muted,
        [data-bs-theme="dark"] .text-secondary {
            color: var(--theme-text-muted) !important;
        }

        [data-bs-theme="dark"] .btn-light {
            background-color: #1f2937 !important;
            border-color: #374151 !important;
            color: #f3f4f6 !important;
        }

        [data-bs-theme="dark"] .btn-light:hover {
            background-color: #374151 !important;
            border-color: #4b5563 !important;
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        [data-bs-theme="dark"] .alert {
            border-color: rgba(255, 255, 255, 0.1);
        }

        .chat-transcript-body {
            background-color: #f8fafc;
        }
        [data-bs-theme="dark"] .chat-transcript-body {
            background-color: var(--theme-chat-messages-bg) !important;
        }

        .card-assistant-bubble {
            background-color: #ffffff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
        }
        [data-bs-theme="dark"] .card-assistant-bubble {
            background-color: var(--theme-card-bg);
            color: var(--theme-body-color);
            border: 1px solid var(--theme-card-border);
        }

        /* Theme Toggle Button in Topbar (Icon Only) */
        .theme-toggle-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            flex-shrink: 0;
        }
        .theme-toggle-btn:hover {
            transform: scale(1.05);
        }
        .theme-toggle-btn i {
            font-size: 0.95rem;
            line-height: 1;
        }

        /* Footer Layout: Left Copyright, Exact Horizontal Center Developer Credit */
        .footer-layout {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            width: 100%;
            min-height: 24px;
        }
        .footer-left {
            grid-column: 1;
            text-align: left;
            justify-self: start;
            max-width: 100%;
        }
        .footer-center {
            grid-column: 2;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            white-space: nowrap;
        }
        .footer-right {
            grid-column: 3;
            visibility: hidden;
            pointer-events: none;
        }
        @media (max-width: 767.98px) {
            .footer-layout {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
            .footer-left {
                text-align: center;
                justify-self: center;
            }
            .footer-center {
                position: static;
                transform: none;
                text-align: center;
                justify-self: center;
                white-space: normal;
            }
            .footer-right {
                display: none;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    @auth
        <div class="app-shell" id="appShell">
            <!-- Mobile Backdrop -->
            <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

            <!-- ChatGPT Collapsible Left Sidebar -->
            <aside class="chatgpt-sidebar" id="appSidebar">
                <!-- Sidebar Header with Branding & Collapse Button -->
                <div class="sidebar-header d-flex align-items-center justify-content-between">
                    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="https://appiflybd.com/" target="_blank" rel="noopener noreferrer" title="Shofol Connect" aria-label="Shofol Connect">
                        <img src="{{ asset('images/shofol-connect-logo.png') }}" alt="Shofol Connect" class="shofol-navbar-logo">
                        <span class="brand-title text-white">Shofol Connect</span>
                    </a>
                    <div class="d-flex align-items-center">
                        <button type="button" id="sidebarCollapseBtn" class="btn btn-sm text-white-50 p-1 d-none d-lg-inline-flex align-items-center justify-content-center" title="Collapse sidebar" aria-label="Collapse sidebar">
                            <i class="bi bi-layout-sidebar-inset fs-5"></i>
                        </button>
                        <button type="button" id="sidebarCloseBtn" class="btn btn-sm text-white-50 p-1 d-lg-none d-inline-flex align-items-center justify-content-center" title="Close sidebar" aria-label="Close sidebar">
                            <i class="bi bi-x-lg fs-5"></i>
                        </button>
                    </div>
                </div>

                <!-- New Support Chat CTA (ChatGPT hallmark) -->
                <div class="px-3 pt-3 pb-1">
                    <a href="{{ route('chat.index') }}" class="sidebar-new-chat-btn {{ request()->routeIs('chat.*') ? 'active-chat' : '' }}">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi bi-plus-lg"></i>
                            <span>New Support Chat</span>
                        </span>
                        <i class="bi bi-robot text-primary"></i>
                    </a>
                </div>

                <!-- Scrollable Navigation Items -->
                <nav class="sidebar-nav-scroll">
                    <!-- User Workspace Navigation -->
                    <div class="sidebar-section-title">User Navigation</div>
                    <a class="sidebar-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>User Portal</span>
                    </a>
                    <a class="sidebar-nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}" href="{{ route('chat.index') }}">
                        <i class="bi bi-robot"></i>
                        <span>AI Assistant</span>
                    </a>
                    <a class="sidebar-nav-link {{ request()->routeIs('conversations.*') ? 'active' : '' }}" href="{{ route('conversations.index') }}">
                        <i class="bi bi-clock-history"></i>
                        <span>History</span>
                    </a>
                    <a class="sidebar-nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}" href="{{ route('tickets.index') }}">
                        <i class="bi bi-ticket-detailed"></i>
                        <span>Support Tickets</span>
                    </a>

                    <!-- Admin Navigation -->
                    @if(Auth::user()->isAdmin())
                        <div class="sidebar-section-title mt-3">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1">Admin</span> Navigation
                        </div>
                        <a class="sidebar-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-shield-lock"></i>
                            <span>Admin Panel</span>
                        </a>
                        <a class="sidebar-nav-link {{ request()->routeIs('admin.knowledge-articles.*') ? 'active' : '' }}" href="{{ route('admin.knowledge-articles.index') }}">
                            <i class="bi bi-journal-bookmark"></i>
                            <span>Knowledge Base</span>
                        </a>
                        <a class="sidebar-nav-link {{ request()->routeIs('admin.modules.*') ? 'active' : '' }}" href="{{ route('admin.modules.index') }}">
                            <i class="bi bi-grid-3x3-gap"></i>
                            <span>Modules</span>
                        </a>
                        <a class="sidebar-nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                            <i class="bi bi-tags"></i>
                            <span>Categories</span>
                        </a>
                        <a class="sidebar-nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}" href="{{ route('admin.faqs.index') }}">
                            <i class="bi bi-question-circle"></i>
                            <span>FAQs</span>
                        </a>
                        <a class="sidebar-nav-link {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">
                            <i class="bi bi-ticket-perforated-fill"></i>
                            <span>Manage Tickets</span>
                        </a>
                        <a class="sidebar-nav-link {{ request()->routeIs('admin.conversations.*') ? 'active' : '' }}" href="{{ route('admin.conversations.index') }}">
                            <i class="bi bi-chat-dots"></i>
                            <span>Conversations</span>
                        </a>
                    @endif
                </nav>

                <!-- Account Section (Fixed at bottom) -->
                <div class="sidebar-account-footer">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                            <div class="user-avatar-circle flex-shrink-0">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-white-50" style="font-size: 0.7rem; line-height: 1.1;">Signed in as:</div>
                                <div class="text-white fw-semibold text-truncate small" title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</div>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            @if(Auth::user()->isAdmin())
                                <span class="badge bg-danger">Admin</span>
                            @else
                                <span class="badge bg-primary">User</span>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2 py-1">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Log Out</span>
                        </button>
                    </form>
                </div>

                <!-- Draggable Sidebar Resize Handle -->
                <div class="sidebar-resizer" id="sidebarResizer" title="Drag to resize sidebar" aria-hidden="true"></div>
            </aside>

            <!-- Main Workspace Container -->
            <div class="main-workspace">
                <!-- Top Workspace Header Bar with Sidebar Toggle -->
                <header class="workspace-topbar">
                    <div class="d-flex align-items-center gap-2">
                        <!-- Clickable Logo shown in top-left when sidebar is hidden (reopens sidebar) -->
                        <button type="button" id="collapsedLogoBtn" class="collapsed-topbar-brand btn btn-link p-0 border-0 text-decoration-none align-items-center" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Open sidebar" aria-label="Open sidebar">
                            <img src="{{ asset('images/shofol-connect-logo.png') }}" alt="Shofol Connect" class="shofol-topbar-logo">
                        </button>

                        <!-- Compact Sidebar Toggle Button in Top Navbar -->
                        <button type="button" id="sidebarToggleBtn" class="btn btn-sm btn-light border text-secondary d-inline-flex align-items-center justify-content-center shadow-sm sidebar-toggle-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Collapse sidebar" aria-label="Toggle sidebar" aria-expanded="true">
                            <i class="bi bi-layout-sidebar fs-6"></i>
                        </button>

                        <span class="fw-semibold text-secondary small d-none d-md-inline">|</span>
                        <span class="fw-semibold text-dark small text-truncate">
                            @yield('title', 'Shofol Connect — AI Support Assistant')
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <!-- Theme Toggle Button in Header Topbar (Icon Only) -->
                        <button type="button"
                                id="topbarThemeToggleBtn"
                                class="btn btn-sm btn-light border shadow-sm theme-toggle-btn"
                                data-bs-toggle="tooltip"
                                data-bs-placement="bottom"
                                title="Switch to Dark Mode"
                                aria-label="Switch to Dark Mode">
                            <i class="bi bi-moon-stars-fill theme-icon-moon text-secondary"></i>
                            <i class="bi bi-sun-fill theme-icon-sun text-warning d-none"></i>
                        </button>

                        @if(request()->routeIs('chat.*'))
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1 small">
                                <i class="bi bi-shield-check"></i> RAG Grounded
                            </span>
                        @else
                            <a href="{{ route('chat.index') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                                <i class="bi bi-robot"></i>
                                <span class="d-none d-sm-inline">AI Assistant</span>
                            </a>
                        @endif
                    </div>
                </header>

                <!-- Workspace Scrollable Body -->
                <main class="workspace-body">
                    <div class="main-content-inner">
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

                    <!-- Footer -->
                    <footer class="footer py-3 text-muted small mt-4">
                        <div class="container-fluid px-3">
                            <div class="footer-layout">
                                <div class="footer-left">
                                    <span>&copy; {{ date('Y') }} <strong>Shofol Connect</strong> — AI Support Assistant MVP. All rights reserved.</span>
                                </div>
                                <div class="footer-center">
                                    <span>Developed by <a href="https://appiflybd.com/" target="_blank" rel="noopener noreferrer" class="footer-credit-link fw-bold">APPIFLY BD LTD</a></span>
                                </div>
                                <div class="footer-right" aria-hidden="true"></div>
                            </div>
                        </div>
                    </footer>
                </main>
            </div>
        </div>
    @else
        <!-- Guest View fallback -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold py-1" href="https://appiflybd.com/" target="_blank" rel="noopener noreferrer" title="Shofol Connect" aria-label="Shofol Connect">
                    <img src="{{ asset('images/shofol-connect-logo.png') }}" alt="Shofol Connect" class="shofol-navbar-logo">
                    <span class="brand-title text-white">Shofol Connect</span>
                </a>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <!-- Guest Theme Toggle Button (Icon Only) -->
                    <button type="button"
                            id="guestThemeToggleBtn"
                            class="btn btn-sm btn-outline-light shadow-sm theme-toggle-btn"
                            data-bs-toggle="tooltip"
                            data-bs-placement="bottom"
                            title="Switch to Dark Mode"
                            aria-label="Switch to Dark Mode">
                        <i class="bi bi-moon-stars-fill theme-icon-moon"></i>
                        <i class="bi bi-sun-fill theme-icon-sun text-warning d-none"></i>
                    </button>
                    <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">Log In</a>
                    @if (Route::has('register'))
                        <a class="btn btn-primary btn-sm" href="{{ route('register') }}">Register</a>
                    @endif
                </div>
            </div>
        </nav>
        <main class="py-4">
            <div class="container">
                @yield('content')
            </div>
        </main>
        <footer class="footer py-3 text-muted small mt-auto border-top bg-white">
            <div class="container">
                <div class="footer-layout">
                    <div class="footer-left">
                        <span>&copy; {{ date('Y') }} <strong>Shofol Connect</strong> — AI Support Assistant MVP. All rights reserved.</span>
                    </div>
                    <div class="footer-center">
                        <span>Developed by <a href="https://appiflybd.com/" target="_blank" rel="noopener noreferrer" class="footer-credit-link fw-bold">APPIFLY BD LTD</a></span>
                    </div>
                    <div class="footer-right" aria-hidden="true"></div>
                </div>
            </div>
        </footer>
    @endauth

    <!-- Bootstrap Bundle JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- ChatGPT-style Sidebar JS Controller with Resizer -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const appShell = document.getElementById('appShell');
            if (!appShell) return;

            const resizer = document.getElementById('sidebarResizer');
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            const collapseBtn = document.getElementById('sidebarCollapseBtn');
            const closeBtn = document.getElementById('sidebarCloseBtn');
            const backdrop = document.getElementById('sidebarBackdrop');

            const isDesktop = () => window.innerWidth >= 992;
            const MIN_WIDTH = 200;
            const MAX_WIDTH = 400;
            const DEFAULT_WIDTH = 260;

            function getSavedWidth() {
                try {
                    const saved = parseInt(localStorage.getItem('shofol_sidebar_width'), 10);
                    if (!isNaN(saved) && saved >= MIN_WIDTH && saved <= MAX_WIDTH) {
                        return saved;
                    }
                } catch (e) {}
                return DEFAULT_WIDTH;
            }

            function setSidebarWidth(width) {
                const clamped = Math.max(MIN_WIDTH, Math.min(MAX_WIDTH, Math.round(width)));
                document.documentElement.style.setProperty('--sidebar-width', clamped + 'px');
                try {
                    localStorage.setItem('shofol_sidebar_width', clamped);
                } catch (e) {}
                return clamped;
            }

            // Restore saved width on desktop
            if (isDesktop()) {
                setSidebarWidth(getSavedWidth());
            }

            const collapsedLogoBtn = document.getElementById('collapsedLogoBtn');

            function updateSidebarToggleTooltip(isCollapsed) {
                if (!toggleBtn) return;
                const title = isCollapsed ? 'Open sidebar' : 'Collapse sidebar';
                toggleBtn.setAttribute('title', title);
                toggleBtn.setAttribute('aria-label', title);
                toggleBtn.setAttribute('data-bs-original-title', title);
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    const inst = bootstrap.Tooltip.getInstance(toggleBtn);
                    if (inst) inst.setContent({ '.tooltip-inner': title });
                }
            }

            function openSidebar(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                if (isDesktop()) {
                    setSidebarWidth(getSavedWidth());
                    appShell.classList.remove('sidebar-collapsed');
                    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
                    updateSidebarToggleTooltip(false);
                    try {
                        localStorage.setItem('shofol_sidebar_collapsed', 'false');
                    } catch (err) {}
                } else {
                    appShell.classList.add('sidebar-mobile-open');
                    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
                }
            }

            function collapseSidebar(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                if (isDesktop()) {
                    appShell.classList.add('sidebar-collapsed');
                    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
                    updateSidebarToggleTooltip(true);
                    try {
                        localStorage.setItem('shofol_sidebar_collapsed', 'true');
                    } catch (err) {}
                } else {
                    closeMobileSidebar(e);
                }
            }

            // Restore desktop collapsed preference
            try {
                const savedState = localStorage.getItem('shofol_sidebar_collapsed');
                if (savedState === 'true' && isDesktop()) {
                    appShell.classList.add('sidebar-collapsed');
                    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
                    updateSidebarToggleTooltip(true);
                } else {
                    appShell.classList.remove('sidebar-collapsed');
                    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
                    updateSidebarToggleTooltip(false);
                }
            } catch (err) {}

            function toggleSidebar(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                if (isDesktop()) {
                    const isCurrentlyCollapsed = appShell.classList.contains('sidebar-collapsed');
                    if (isCurrentlyCollapsed) {
                        openSidebar(e);
                    } else {
                        collapseSidebar(e);
                    }
                } else {
                    const isOpen = appShell.classList.toggle('sidebar-mobile-open');
                    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                }
            }

            function closeMobileSidebar(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                appShell.classList.remove('sidebar-mobile-open');
                if (toggleBtn && !isDesktop()) {
                    toggleBtn.setAttribute('aria-expanded', 'false');
                }
            }

            if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
            if (collapseBtn) collapseBtn.addEventListener('click', collapseSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeMobileSidebar);
            if (backdrop) backdrop.addEventListener('click', closeMobileSidebar);
            if (collapsedLogoBtn) collapsedLogoBtn.addEventListener('click', openSidebar);

            // Initialize Bootstrap tooltips on topbar controls
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                [toggleBtn, collapsedLogoBtn].forEach(el => {
                    if (el) {
                        new bootstrap.Tooltip(el, {
                            placement: 'bottom',
                            trigger: 'hover focus'
                        });
                    }
                });
            }

            // Resizing Drag Handlers (Desktop Only)
            let isDragging = false;

            if (resizer) {
                resizer.addEventListener('mousedown', function (e) {
                    if (!isDesktop() || appShell.classList.contains('sidebar-collapsed')) return;
                    e.preventDefault();
                    e.stopPropagation();
                    isDragging = true;
                    appShell.classList.add('is-resizing');

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                });
            }

            function onMouseMove(e) {
                if (!isDragging || !isDesktop()) return;
                setSidebarWidth(e.clientX);
            }

            function onMouseUp(e) {
                if (!isDragging) return;
                isDragging = false;
                appShell.classList.remove('is-resizing');
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            }

            // On mobile, auto-close sidebar when a navigation link is clicked
            const navLinks = document.querySelectorAll('.chatgpt-sidebar .sidebar-nav-link, .chatgpt-sidebar .sidebar-new-chat-btn');
            navLinks.forEach(link => {
                link.addEventListener('click', function () {
                    if (!isDesktop()) {
                        closeMobileSidebar();
                    }
                });
            });

            // Adjust on window resize
            window.addEventListener('resize', function () {
                if (isDesktop()) {
                    closeMobileSidebar();
                    setSidebarWidth(getSavedWidth());
                    try {
                        if (localStorage.getItem('shofol_sidebar_collapsed') === 'true') {
                            appShell.classList.add('sidebar-collapsed');
                            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
                        } else {
                            appShell.classList.remove('sidebar-collapsed');
                            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
                        }
                    } catch (err) {}
                } else {
                    appShell.classList.remove('sidebar-collapsed');
                }
            });

            // ESC key closes mobile sidebar
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && appShell.classList.contains('sidebar-mobile-open')) {
                    closeMobileSidebar(e);
                }
            });

            // THEME CONTROLLER
            const THEME_KEY = 'shofol_theme';

            function getPreferredTheme() {
                try {
                    const saved = localStorage.getItem(THEME_KEY);
                    if (saved === 'dark' || saved === 'light') {
                        return saved;
                    }
                } catch (e) {}
                return 'light'; // Default Light Mode for first-time visitors
            }

            function setTheme(theme, save) {
                if (save === undefined) save = true;
                const targetTheme = theme === 'dark' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-bs-theme', targetTheme);
                document.documentElement.setAttribute('data-theme', targetTheme);

                if (save) {
                    try {
                        localStorage.setItem(THEME_KEY, targetTheme);
                    } catch (e) {}
                }

                // Update all theme toggle buttons across the UI
                updateToggleButtons(targetTheme);
            }

            function updateToggleButtons(theme) {
                const isDark = theme === 'dark';
                const toggleButtons = document.querySelectorAll('.theme-toggle-btn');
                const tooltipText = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';

                toggleButtons.forEach(btn => {
                    const moonIcon = btn.querySelector('.theme-icon-moon');
                    const sunIcon = btn.querySelector('.theme-icon-sun');

                    if (isDark) {
                        if (moonIcon) moonIcon.classList.add('d-none');
                        if (sunIcon) sunIcon.classList.remove('d-none');
                    } else {
                        if (moonIcon) moonIcon.classList.remove('d-none');
                        if (sunIcon) sunIcon.classList.add('d-none');
                    }

                    btn.setAttribute('title', tooltipText);
                    btn.setAttribute('aria-label', tooltipText);
                    btn.setAttribute('data-bs-original-title', tooltipText);

                    // Update Bootstrap tooltip instance if available
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        const existingTooltip = bootstrap.Tooltip.getInstance(btn);
                        if (existingTooltip) {
                            existingTooltip.setContent({ '.tooltip-inner': tooltipText });
                        } else {
                            new bootstrap.Tooltip(btn, {
                                placement: 'bottom',
                                trigger: 'hover focus'
                            });
                        }
                    }
                });
            }

            function toggleTheme(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                setTheme(newTheme, true);
            }

            // Bind click handler to all theme buttons
            document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
                btn.addEventListener('click', toggleTheme);
            });

            // Sync toggle button states to currently active theme
            updateToggleButtons(document.documentElement.getAttribute('data-bs-theme') || getPreferredTheme());
        });
    </script>
    @yield('scripts')
</body>
</html>

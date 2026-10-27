<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Anti-FOUC Theme Script -->
        <script>
            (function () {
                try {
                    var theme = localStorage.getItem('shofol_theme') || 'light';
                    document.documentElement.setAttribute('data-bs-theme', theme);
                    document.documentElement.setAttribute('data-theme', theme);
                    document.documentElement.classList.toggle('dark', theme === 'dark');
                } catch (e) {
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 dark:text-gray-100 antialiased bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
        <!-- Guest Theme Toggle Button -->
        <div class="fixed top-4 right-4 z-50">
            <button type="button" id="guestThemeToggleBtn" onclick="toggleGuestTheme()" class="p-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 shadow-sm transition theme-toggle-btn" title="Toggle Theme" aria-label="Toggle theme">
                <span class="theme-icon-moon dark:hidden">🌙</span>
                <span class="theme-icon-sun hidden dark:inline">☀️</span>
            </button>
        </div>

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>

        <script>
            function toggleGuestTheme() {
                var current = localStorage.getItem('shofol_theme') === 'dark' ? 'dark' : 'light';
                var next = current === 'dark' ? 'light' : 'dark';
                try {
                    localStorage.setItem('shofol_theme', next);
                } catch (e) {}
                document.documentElement.setAttribute('data-bs-theme', next);
                document.documentElement.setAttribute('data-theme', next);
                document.documentElement.classList.toggle('dark', next === 'dark');
            }
        </script>
    </body>
</html>

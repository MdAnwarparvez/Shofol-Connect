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

        <!-- Bootstrap 5.3 & Icons -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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

        <div class="min-h-screen flex flex-col sm:justify-center items-center py-8 px-4 bg-gray-100 dark:bg-gray-900">
            @if (!$hideLogo)
                <div class="mb-3">
                    <a href="/" title="Shofol Connect">
                        <x-application-logo class="w-16 h-16 object-contain" />
                    </a>
                </div>
            @endif

            <div class="{{ $cardClass ?? 'w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg' }}">
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
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    </body>
</html>

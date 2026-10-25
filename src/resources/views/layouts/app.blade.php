<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'AQDesk') — {{ config('app.name', 'AQDesk') }}</title>
    <meta name="description" content="AQDesk — a community, learning and knowledge platform for emerging technologies.">

    {{-- Theme is applied before first paint so a dark-mode user never sees a white flash. --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('aq-theme');
                if (!t) { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                document.documentElement.setAttribute('data-bs-theme', t);
            } catch (e) {}
        })();
    </script>

    {{-- Bootstrap 5, Bootstrap Icons and the two typefaces come from a CDN so the
         project runs with `composer install` alone — no Node.js or npm build step. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300..800;1,400&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/aqdesk.css') }}" rel="stylesheet">
</head>
<body>

    {{-- Accessibility: keyboard users can jump straight to the content. --}}
    <a class="visually-hidden-focusable skip-link" href="#main-content">Skip to main content</a>

    <div class="aq-shell">
        @include('partials.sidebar')

        <div class="aq-main">
            @include('partials.navbar')

            <main id="main-content" class="aq-content" tabindex="-1">
                <div class="@yield('container', 'aq-container')">
                    @include('partials.alerts')
                    @yield('content')
                </div>
            </main>

            @include('partials.footer')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Light / dark toggle. Preference is remembered per browser.
        document.querySelectorAll('[data-aq-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var root = document.documentElement;
                var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-bs-theme', next);
                try { localStorage.setItem('aq-theme', next); } catch (e) {}
                document.querySelectorAll('[data-aq-theme-toggle] i').forEach(function (i) {
                    i.className = next === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
                });
                btn.setAttribute('aria-label', next === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
            });
        });
    </script>
    @stack('scripts')
</body>
</html>

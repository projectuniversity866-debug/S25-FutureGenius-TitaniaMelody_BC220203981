<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'AQDesk') — {{ config('app.name', 'AQDesk') }}</title>

    <script>
        (function () {
            try {
                var t = localStorage.getItem('aq-theme');
                if (!t) { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                document.documentElement.setAttribute('data-bs-theme', t);
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300..800;1,400&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/aqdesk.css') }}" rel="stylesheet">
</head>
<body>
    <a class="visually-hidden-focusable skip-link" href="#main-content">Skip to main content</a>

    <div class="aq-auth">
        {{-- Left: brand panel. Hidden below lg so the form gets the whole screen. --}}
        <aside class="aq-auth-aside">
            <a class="aq-brand px-0" href="{{ route('home') }}">
                <span class="aq-brand-mark"><i class="bi bi-question-lg" aria-hidden="true"></i></span>
                <span>AQ<em>Desk</em></span>
            </a>

            <div>
                <p class="aq-label mb-3">Community · Learning · Knowledge</p>
                <p class="aq-quote mb-4">
                    One desk where students, instructors and industry experts
                    answer each other's questions.
                </p>
                <ul class="list-unstyled d-grid gap-2 mb-0" style="font-size: .875rem; color: var(--aq-ink-2);">
                    <li><i class="bi bi-check2 me-2" style="color: var(--aq-accent);" aria-hidden="true"></i> Ask, answer and upvote across four topic tracks</li>
                    <li><i class="bi bi-check2 me-2" style="color: var(--aq-accent);" aria-hidden="true"></i> Role-based access for members, experts and admins</li>
                    <li><i class="bi bi-check2 me-2" style="color: var(--aq-accent);" aria-hidden="true"></i> Jobs and internships with application tracking</li>
                </ul>
            </div>

            <p class="mb-0" style="font-size: .75rem; color: var(--aq-muted);">
                CS619 Final Year Project — Prototype Phase
            </p>
        </aside>

        {{-- Right: the form itself. --}}
        <div class="aq-auth-panel">
            <div class="aq-auth-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a class="aq-brand p-0 d-lg-none" href="{{ route('home') }}">
                        <span class="aq-brand-mark"><i class="bi bi-question-lg" aria-hidden="true"></i></span>
                        <span>AQ<em>Desk</em></span>
                    </a>
                    <button type="button" class="aq-icon-btn ms-auto" data-aq-theme-toggle
                            aria-label="Switch colour theme">
                        <i class="bi bi-moon-stars" aria-hidden="true"></i>
                    </button>
                </div>

                <main id="main-content" tabindex="-1">
                    @include('partials.alerts')
                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('[data-aq-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var root = document.documentElement;
                var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-bs-theme', next);
                try { localStorage.setItem('aq-theme', next); } catch (e) {}
                btn.querySelector('i').className = next === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
            });
        });
    </script>
</body>
</html>

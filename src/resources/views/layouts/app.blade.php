<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'AQDesk') — {{ config('app.name', 'AQDesk') }}</title>
    <meta name="description" content="AQDesk — a community, learning and knowledge platform for emerging technologies.">

    {{-- Bootstrap 5 and Bootstrap Icons are loaded from a CDN so the project
         runs with `composer install` alone — no Node.js or npm build step. --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/aqdesk.css') }}" rel="stylesheet">
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">

    {{-- Accessibility: keyboard users can jump straight to the content. --}}
    <a class="visually-hidden-focusable skip-link" href="#main-content">Skip to main content</a>

    @include('partials.navbar')

    <main id="main-content" class="flex-grow-1 py-4" tabindex="-1">
        <div class="container">
            @include('partials.alerts')
            @yield('content')
        </div>
    </main>

    @include('partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>

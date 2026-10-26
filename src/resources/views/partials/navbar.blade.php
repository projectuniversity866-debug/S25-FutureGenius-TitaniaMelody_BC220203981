@php
    $user = auth()->user();
@endphp

{{-- Top bar: context, search, theme switch and the primary action.
     Navigation itself lives in partials/sidebar.blade.php. --}}
<header class="aq-topbar">
    <button class="aq-icon-btn d-lg-none" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#aqSidebar"
            aria-controls="aqSidebar" aria-label="Open navigation menu">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <div class="min-w-0 d-none d-md-block">
        <p class="aq-topbar-title text-truncate">@yield('title', 'AQDesk')</p>
        <p class="aq-topbar-sub">@yield('subtitle', 'Answer Question community platform')</p>
    </div>

    <form class="aq-search ms-md-auto" method="GET" action="{{ route('questions.index') }}" role="search">
        <label for="topbar-search" class="visually-hidden">Search questions</label>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="topbar-search" name="q" value="{{ is_string(request('q')) ? request('q') : '' }}"
               placeholder="Search questions…" autocomplete="off">
    </form>

    <button type="button" class="aq-icon-btn" data-aq-theme-toggle aria-label="Switch colour theme">
        <i class="bi bi-moon-stars" aria-hidden="true"></i>
    </button>

    @auth
        @include('platform::partials.bell')

        <a href="{{ route('questions.create') }}" class="btn btn-accent btn-sm d-none d-sm-inline-flex">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Ask
        </a>

        <div class="dropdown">
            <button class="aq-user-chip dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <x-avatar :user="$user" size="xs" />
                <span class="d-none d-lg-inline">{{ \Illuminate\Support\Str::limit($user->name, 14) }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 pt-2 pb-1">
                    <div class="aq-label">Signed in as</div>
                    <div class="aq-text-sm aq-strong">{{ $user->email }}</div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('dashboard') }}">
                    <i class="bi bi-columns-gap me-2" aria-hidden="true"></i> Dashboard</a></li>
                <li><a class="dropdown-item" href="{{ route('profile.show', $user) }}">
                    <i class="bi bi-person me-2" aria-hidden="true"></i> Public profile</a></li>
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                    <i class="bi bi-person-gear me-2" aria-hidden="true"></i> Edit profile</a></li>
                <li><a class="dropdown-item" href="{{ route('settings.show') }}">
                    <i class="bi bi-gear me-2" aria-hidden="true"></i> Account settings</a></li>
                <li><a class="dropdown-item" href="{{ route('saved.index') }}">
                    <i class="bi bi-bookmark me-2" aria-hidden="true"></i> Saved</a></li>
                <li><a class="dropdown-item" href="{{ route('applications.index') }}">
                    <i class="bi bi-send me-2" aria-hidden="true"></i> My applications</a></li>
                <li><a class="dropdown-item" href="{{ route('alerts.index') }}">
                    <i class="bi bi-bell me-2" aria-hidden="true"></i> Job alerts</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item aq-danger">
                            <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i> Sign out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    @else
        <a href="{{ route('login') }}" class="btn btn-quiet btn-sm">Sign in</a>
        <a href="{{ route('register') }}" class="btn btn-accent btn-sm d-none d-sm-inline-flex">Create account</a>
    @endauth
</header>

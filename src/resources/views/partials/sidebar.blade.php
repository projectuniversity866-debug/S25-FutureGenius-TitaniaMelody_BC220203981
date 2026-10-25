@php
    $user = auth()->user();
@endphp

{{-- Primary navigation. Static from lg up, an off-canvas drawer below it
     (Bootstrap's responsive off-canvas — no custom JS needed). --}}
<nav class="aq-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="aqSidebar"
     aria-label="Main navigation">

    <div class="d-flex align-items-center justify-content-between">
        <a class="aq-brand" href="{{ route('home') }}">
            <span class="aq-brand-mark"><i class="bi bi-question-lg" aria-hidden="true"></i></span>
            <span>AQ<em>Desk</em></span>
        </a>
        <button type="button" class="btn-close me-3 d-lg-none"
                data-bs-dismiss="offcanvas" data-bs-target="#aqSidebar" aria-label="Close navigation"></button>
    </div>

    <ul class="aq-sidenav">
        <li><a class="{{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">
            <i class="bi bi-house-door" aria-hidden="true"></i> Home
        </a></li>

        @auth
            <li><a class="{{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-columns-gap" aria-hidden="true"></i> Dashboard
            </a></li>
        @endauth

        <li class="aq-sidenav-group"><span class="aq-label">Community</span></li>

        <li><a class="{{ request()->routeIs('questions.*') ? 'is-active' : '' }}" href="{{ route('questions.index') }}">
            <i class="bi bi-patch-question" aria-hidden="true"></i> Questions
        </a></li>
        <li><a class="{{ request()->routeIs('jobs.index') || request()->routeIs('jobs.show') ? 'is-active' : '' }}"
               href="{{ route('jobs.index') }}">
            <i class="bi bi-briefcase" aria-hidden="true"></i> Jobs &amp; internships
        </a></li>

        @auth
            <li><a class="{{ request()->routeIs('applications.*') ? 'is-active' : '' }}" href="{{ route('applications.index') }}">
                <i class="bi bi-send" aria-hidden="true"></i> My applications
            </a></li>
            @if ($user->canPostJobs())
                <li><a class="{{ request()->routeIs('jobs.mine') || request()->routeIs('jobs.applicants') ? 'is-active' : '' }}"
                       href="{{ route('jobs.mine') }}">
                    <i class="bi bi-clipboard-check" aria-hidden="true"></i> My postings
                </a></li>
            @endif
            <li><a class="{{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ route('profile.show', $user) }}">
                <i class="bi bi-person" aria-hidden="true"></i> My profile
            </a></li>
        @endauth

        @auth
            @if ($user->isAdmin())
                <li class="aq-sidenav-group"><span class="aq-label">Administration</span></li>
                <li><a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">
                    <i class="bi bi-people" aria-hidden="true"></i> Users
                </a></li>
                <li><a class="{{ request()->routeIs('admin.questions.*') ? 'is-active' : '' }}" href="{{ route('admin.questions.index') }}">
                    <i class="bi bi-shield-check" aria-hidden="true"></i> Moderate questions
                </a></li>
                <li><a class="{{ request()->routeIs('admin.jobs.*') ? 'is-active' : '' }}" href="{{ route('admin.jobs.index') }}">
                    <i class="bi bi-shield-check" aria-hidden="true"></i> Moderate opportunities
                </a></li>
            @endif
        @endauth

        {{-- Modules deferred to the final project. Shown, but clearly marked,
             so the full AQDesk scope stays visible to the reviewer. --}}
        <li class="aq-sidenav-group"><span class="aq-label">Phase 2</span></li>
        <li><a class="is-muted" href="{{ route('modules.coming-soon', 'projects') }}">
            <i class="bi bi-kanban" aria-hidden="true"></i> Projects <span class="aq-nav-soon">soon</span>
        </a></li>
        <li><a class="is-muted" href="{{ route('modules.coming-soon', 'events') }}">
            <i class="bi bi-calendar-event" aria-hidden="true"></i> Events &amp; podcasts <span class="aq-nav-soon">soon</span>
        </a></li>
        <li><a class="is-muted" href="{{ route('modules.coming-soon', 'consulting') }}">
            <i class="bi bi-clipboard-data" aria-hidden="true"></i> Consulting <span class="aq-nav-soon">soon</span>
        </a></li>
        <li><a class="is-muted" href="{{ route('modules.coming-soon', 'newsletter') }}">
            <i class="bi bi-envelope-paper" aria-hidden="true"></i> Newsletter <span class="aq-nav-soon">soon</span>
        </a></li>
    </ul>

    <div class="aq-sidefoot">
        @auth
            <div class="d-flex align-items-center gap-2">
                @include('partials.avatar', ['user' => $user, 'size' => 34])
                <div class="min-w-0 flex-grow-1">
                    <div class="text-truncate" style="font-size: .8125rem; font-weight: 600;">{{ $user->name }}</div>
                    <div class="aq-label" style="font-size: .625rem;">{{ $user->roleName() }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="aq-icon-btn" aria-label="Sign out" title="Sign out">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        @else
            <div class="d-grid gap-2">
                <a href="{{ route('register') }}" class="btn btn-accent btn-sm">Create account</a>
                <a href="{{ route('login') }}" class="btn btn-quiet btn-sm">Sign in</a>
            </div>
        @endauth
    </div>
</nav>

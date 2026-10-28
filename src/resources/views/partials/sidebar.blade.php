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
        <li><a class="{{ request()->routeIs('tags.*') ? 'is-active' : '' }}" href="{{ route('tags.index') }}">
            <i class="bi bi-tags" aria-hidden="true"></i> Tags
        </a></li>
        <li><a class="{{ request()->routeIs('articles.*') ? 'is-active' : '' }}" href="{{ route('articles.index') }}">
            <i class="bi bi-journal-richtext" aria-hidden="true"></i> Knowledge Hub
        </a></li>
        <li><a class="{{ request()->routeIs('learning.*') ? 'is-active' : '' }}" href="{{ route('learning.index') }}">
            <i class="bi bi-signpost-2" aria-hidden="true"></i> Learning Hub
        </a></li>
        <li><a class="{{ request()->routeIs('jobs.index') || request()->routeIs('jobs.show') ? 'is-active' : '' }}"
               href="{{ route('jobs.index') }}">
            <i class="bi bi-briefcase" aria-hidden="true"></i> Opportunities
        </a></li>
        <li><a class="{{ request()->routeIs('organizations.*') ? 'is-active' : '' }}" href="{{ route('organizations.index') }}">
            <i class="bi bi-buildings" aria-hidden="true"></i> Organizations
        </a></li>
        <li><a class="{{ request()->routeIs('events.*') ? 'is-active' : '' }}" href="{{ route('events.index') }}">
            <i class="bi bi-calendar-event" aria-hidden="true"></i> Events
        </a></li>
        <li><a class="{{ request()->routeIs('podcasts.*') ? 'is-active' : '' }}" href="{{ route('podcasts.index') }}">
            <i class="bi bi-mic" aria-hidden="true"></i> Podcasts
        </a></li>
        <li><a class="{{ request()->routeIs('consultants.*', 'consulting.profile*', 'consulting.availability', 'consulting.hours.*', 'consulting.days-off.*') ? 'is-active' : '' }}" href="{{ route('consultants.index') }}">
            <i class="bi bi-person-video3" aria-hidden="true"></i> Consultants
        </a></li>
        <li><a class="{{ request()->routeIs('newsletter.*') ? 'is-active' : '' }}" href="{{ route('newsletter.show') }}">
            <i class="bi bi-envelope-paper" aria-hidden="true"></i> Newsletter
        </a></li>
        <li><a class="{{ request()->routeIs('announcements.*') ? 'is-active' : '' }}" href="{{ route('announcements.index') }}">
            <i class="bi bi-megaphone" aria-hidden="true"></i> Updates
        </a></li>

        @auth
            <li><a class="{{ request()->routeIs('saved.*') ? 'is-active' : '' }}" href="{{ route('saved.index') }}">
                <i class="bi bi-bookmark" aria-hidden="true"></i> Saved
            </a></li>
            <li><a class="{{ request()->routeIs('applications.*') ? 'is-active' : '' }}" href="{{ route('applications.index') }}">
                <i class="bi bi-send" aria-hidden="true"></i> My applications
            </a></li>
            @if ($user->canPostJobs())
                <li><a class="{{ request()->routeIs('jobs.mine') || request()->routeIs('jobs.applicants') ? 'is-active' : '' }}"
                       href="{{ route('jobs.mine') }}">
                    <i class="bi bi-clipboard-check" aria-hidden="true"></i> My postings
                </a></li>
            @endif
            <li><a class="{{ request()->routeIs('projects.*') ? 'is-active' : '' }}" href="{{ route('projects.index') }}">
                <i class="bi bi-kanban" aria-hidden="true"></i> Projects
            </a></li>
            <li><a class="{{ request()->routeIs('consulting.sessions.*') ? 'is-active' : '' }}" href="{{ route('consulting.sessions.index') }}">
                <i class="bi bi-calendar-week" aria-hidden="true"></i> My sessions
            </a></li>
            <li><a class="{{ request()->routeIs('consulting.proposals.*') ? 'is-active' : '' }}" href="{{ route('consulting.proposals.index') }}">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i> Proposals
            </a></li>
            @can('consulting.offer')
                <li><a class="{{ request()->routeIs('consulting.clients.*') ? 'is-active' : '' }}" href="{{ route('consulting.clients.index') }}">
                    <i class="bi bi-person-lines-fill" aria-hidden="true"></i> My clients
                </a></li>
            @endcan
            @can('forms.create')
                <li><a class="{{ request()->routeIs('forms.*') ? 'is-active' : '' }}" href="{{ route('forms.index') }}">
                    <i class="bi bi-ui-checks" aria-hidden="true"></i> My forms
                </a></li>
            @endcan
            <li><a class="{{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ route('profile.show', $user) }}">
                <i class="bi bi-person" aria-hidden="true"></i> My profile
            </a></li>
        @endauth

        @auth
            @canany(['users.manage', 'content.moderate', 'activity.view', 'experts.verify', 'newsletter.send', 'announcements.manage'])
                <li class="aq-sidenav-group"><span class="aq-label">Administration</span></li>
                @can('users.manage')
                    <li><a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people" aria-hidden="true"></i> Users
                    </a></li>
                    <li><a class="{{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}" href="{{ route('admin.roles.index') }}">
                        <i class="bi bi-key" aria-hidden="true"></i> Roles &amp; permissions
                    </a></li>
                @endcan
                @can('content.moderate')
                    @php $openReports = \App\Modules\Governance\Models\Report::where('status', 'open')->count(); @endphp
                    <li><a class="{{ request()->routeIs('admin.reports.*', 'admin.moderation.*') ? 'is-active' : '' }}" href="{{ route('admin.reports.index') }}">
                        <i class="bi bi-flag" aria-hidden="true"></i> Reports
                        @if ($openReports)<span class="aq-tabs-count ms-auto">{{ $openReports }}<span class="visually-hidden"> open</span></span>@endif
                    </a></li>
                    <li><a class="{{ request()->routeIs('admin.questions.*') ? 'is-active' : '' }}" href="{{ route('admin.questions.index') }}">
                        <i class="bi bi-shield-check" aria-hidden="true"></i> Moderate questions
                    </a></li>
                    <li><a class="{{ request()->routeIs('admin.jobs.*') ? 'is-active' : '' }}" href="{{ route('admin.jobs.index') }}">
                        <i class="bi bi-shield-check" aria-hidden="true"></i> Moderate opportunities
                    </a></li>
                @endcan
                @can('experts.verify')
                    <li><a class="{{ request()->routeIs('admin.verifications.*') ? 'is-active' : '' }}" href="{{ route('admin.verifications.index') }}">
                        <i class="bi bi-patch-check" aria-hidden="true"></i> Expert verification
                    </a></li>
                @endcan
                @can('announcements.manage')
                    <li><a class="{{ request()->routeIs('admin.announcements.*') ? 'is-active' : '' }}" href="{{ route('admin.announcements.index') }}">
                        <i class="bi bi-megaphone" aria-hidden="true"></i> Announcements
                    </a></li>
                @endcan
                @can('newsletter.send')
                    <li><a class="{{ request()->routeIs('admin.newsletter.*') ? 'is-active' : '' }}" href="{{ route('admin.newsletter.index') }}">
                        <i class="bi bi-envelope-paper" aria-hidden="true"></i> Newsletter
                    </a></li>
                @endcan
                @can('activity.view')
                    <li><a class="{{ request()->routeIs('admin.activity.*') ? 'is-active' : '' }}" href="{{ route('admin.activity.index') }}">
                        <i class="bi bi-clock-history" aria-hidden="true"></i> Activity log
                    </a></li>
                @endcan
            @endcanany
        @endauth
    </ul>

    {{-- Visitors: on large screens the top bar already has Sign in and Create
         account, so the menu keeps its full height; on phones the top bar has
         no room for both, so they stay here. --}}
    <div class="aq-sidefoot @guest d-lg-none @endguest">
        @auth
            <div class="d-flex align-items-center gap-2">
                <x-avatar :user="$user" size="sm" />
                <div class="min-w-0 flex-grow-1">
                    <div class="text-truncate aq-text-sm aq-strong">{{ $user->name }}</div>
                    <div class="aq-label aq-sidefoot-role">{{ $user->roleName() }}</div>
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

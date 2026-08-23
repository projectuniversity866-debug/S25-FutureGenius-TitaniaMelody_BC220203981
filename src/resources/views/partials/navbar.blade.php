@php
    $user = auth()->user();
@endphp

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('home') }}">
            <i class="bi bi-question-diamond-fill text-info" aria-hidden="true"></i>
            <span>AQ<span class="text-info">Desk</span></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('questions.*') ? 'active' : '' }}"
                       href="{{ route('questions.index') }}">
                        <i class="bi bi-patch-question" aria-hidden="true"></i> Community Q&amp;A
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('jobs.*') ? 'active' : '' }}"
                       href="{{ route('jobs.index') }}">
                        <i class="bi bi-briefcase" aria-hidden="true"></i> Jobs &amp; Internships
                    </a>
                </li>

                {{-- Modules deferred to the final project. Shown, but clearly
                     marked, so the full AQDesk scope stays visible. --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-grid" aria-hidden="true"></i> More modules
                    </a>
                    <ul class="dropdown-menu">
                        <li><h6 class="dropdown-header">Planned for Phase 2</h6></li>
                        <li><a class="dropdown-item" href="{{ route('modules.coming-soon', 'projects') }}">
                            <i class="bi bi-kanban me-1" aria-hidden="true"></i> Project Collaboration</a></li>
                        <li><a class="dropdown-item" href="{{ route('modules.coming-soon', 'events') }}">
                            <i class="bi bi-calendar-event me-1" aria-hidden="true"></i> Events &amp; Podcasts</a></li>
                        <li><a class="dropdown-item" href="{{ route('modules.coming-soon', 'consulting') }}">
                            <i class="bi bi-briefcase me-1" aria-hidden="true"></i> Consulting</a></li>
                        <li><a class="dropdown-item" href="{{ route('modules.coming-soon', 'newsletter') }}">
                            <i class="bi bi-envelope-paper me-1" aria-hidden="true"></i> Newsletter</a></li>
                    </ul>
                </li>

                @auth
                    @if ($user->isAdmin())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-warning" href="#" role="button"
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-shield-lock" aria-hidden="true"></i> Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">Manage users</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.questions.index') }}">Moderate questions</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.jobs.index') }}">Moderate opportunities</a></li>
                            </ul>
                        </li>
                    @endif
                @endauth
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center">
                @guest
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Sign in</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-info btn-sm fw-semibold" href="{{ route('register') }}">Create account</a>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm me-lg-2" href="{{ route('questions.create') }}">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Ask a question
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            @include('partials.avatar', ['user' => $user, 'size' => 28])
                            <span>{{ \Illuminate\Support\Str::limit($user->name, 16) }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="px-3 py-2 small text-muted border-bottom">
                                Signed in as<br>
                                <span class="badge text-bg-secondary mt-1">{{ $user->roleName() }}</span>
                            </li>
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i> Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.show', $user) }}">
                                <i class="bi bi-person me-1" aria-hidden="true"></i> My public profile</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-gear me-1" aria-hidden="true"></i> Edit profile</a></li>
                            <li><a class="dropdown-item" href="{{ route('applications.index') }}">
                                <i class="bi bi-send me-1" aria-hidden="true"></i> My applications</a></li>
                            @if ($user->canPostJobs())
                                <li><a class="dropdown-item" href="{{ route('jobs.mine') }}">
                                    <i class="bi bi-clipboard-check me-1" aria-hidden="true"></i> Opportunities I posted</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Sign out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>

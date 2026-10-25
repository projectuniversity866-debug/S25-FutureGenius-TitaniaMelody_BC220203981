@extends('layouts.app')
@section('title', $job->title)
@section('subtitle', 'Jobs & opportunities')

@php
    $me = auth()->user();
    $canManage = $me && ($me->id === $job->user_id || $me->canModerate());
@endphp

@section('content')
<nav aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('jobs.index') }}">Jobs &amp; opportunities</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $job->title }}</li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-8">
        <article class="card">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <span class="aq-job-logo" style="width: 48px; height: 48px; flex: 0 0 48px; font-size: 1.125rem;"
                          aria-hidden="true">{{ \Illuminate\Support\Str::substr($job->company, 0, 1) }}</span>
                    <div class="min-w-0">
                        <h1 style="font-size: 1.375rem; font-weight: 700; margin: 0 0 .25rem;">{{ $job->title }}</h1>
                        <p class="mb-0" style="font-size: .875rem; color: var(--aq-muted);">
                            {{ $job->company }}@if ($job->location) · {{ $job->location }} @endif
                            · posted {{ $job->created_at->diffForHumans(null, true) }} ago
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="badge {{ $job->type === 'internship' ? 'text-bg-warning' : 'text-bg-primary' }}">
                        {{ ucfirst($job->type) }}
                    </span>
                    <span class="badge text-bg-light text-capitalize">{{ $job->work_mode }}</span>
                    @if (! $job->is_active)
                        <span class="badge text-bg-secondary">Unpublished</span>
                    @endif
                    @if ($job->isExpired())
                        <span class="badge text-bg-danger">Deadline passed</span>
                    @endif
                </div>

                <h2 class="aq-label mb-2">Description</h2>
                <div class="prose mb-4">{{ $job->description }}</div>

                @if ($job->requirements)
                    <h2 class="aq-label mb-2">Requirements</h2>
                    <div class="prose mb-4">{{ $job->requirements }}</div>
                @endif

                @if ($canManage)
                    <div class="d-flex flex-wrap gap-2 pt-3" style="border-top: 1px solid var(--aq-border);">
                        <a href="{{ route('jobs.edit', $job) }}" class="btn btn-sm btn-quiet">
                            <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit
                        </a>
                        <a href="{{ route('jobs.applicants', $job) }}" class="btn btn-sm btn-quiet">
                            <i class="bi bi-people me-1" aria-hidden="true"></i> Applicants ({{ $job->applications_count }})
                        </a>
                        <form method="POST" action="{{ route('jobs.destroy', $job) }}"
                              onsubmit="return confirm('Delete this opportunity and all of its applications?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i> Delete
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </article>
    </div>

    <div class="col-lg-4">
        <aside class="card mb-3">
            <div class="card-body">
                <h2 class="aq-label mb-3">At a glance</h2>
                <dl class="aq-dl mb-4">
                    <dt>Salary</dt><dd>{{ $job->salaryRange() }}</dd>
                    <dt>Work mode</dt><dd class="text-capitalize">{{ $job->work_mode }}</dd>
                    <dt>Deadline</dt><dd>{{ $job->deadline?->format('d M Y') ?? 'Open until filled' }}</dd>
                    <dt>Applicants</dt><dd class="aq-num">{{ $job->applications_count }}</dd>
                </dl>

                @auth
                    @if ($hasApplied)
                        <div class="alert alert-success mb-0" role="status">
                            <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
                            You have already applied.
                            <a href="{{ route('applications.index') }}" class="alert-link">View my applications</a>
                        </div>
                    @elseif (! $job->isOpen())
                        <p class="mb-0" style="font-size: .8125rem; color: var(--aq-muted);">This opportunity is closed for applications.</p>
                    @elseif ($me->id === $job->user_id)
                        <p class="mb-0" style="font-size: .8125rem; color: var(--aq-muted);">You published this opportunity.</p>
                    @else
                        <a href="{{ route('jobs.apply', $job) }}" class="btn btn-accent w-100">
                            <i class="bi bi-send me-1" aria-hidden="true"></i> Apply now
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-quiet w-100">Sign in to apply</a>
                @endauth
            </div>
        </aside>

        <aside class="card">
            <div class="card-body">
                <h2 class="aq-label mb-3">Posted by</h2>
                <div class="d-flex align-items-center gap-3">
                    @include('partials.avatar', ['user' => $job->user, 'size' => 40])
                    <div class="min-w-0">
                        <a href="{{ route('profile.show', $job->user) }}"
                           class="fw-semibold text-decoration-none d-block">{{ $job->user->name }}</a>
                        <span class="badge text-bg-light">{{ $job->user->roleName() }}</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

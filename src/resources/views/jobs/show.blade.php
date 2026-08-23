@extends('layouts.app')
@section('title', $job->title)

@php
    $me = auth()->user();
    $canManage = $me && ($me->id === $job->user_id || $me->canModerate());
@endphp

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('jobs.index') }}">Jobs &amp; Opportunities</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $job->title }}</li>
            </ol>
        </nav>

        <article class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge {{ $job->type === 'internship' ? 'text-bg-warning' : 'text-bg-primary' }}">
                        {{ ucfirst($job->type) }}
                    </span>
                    <span class="badge text-bg-light border text-capitalize">{{ $job->work_mode }}</span>
                    @if (! $job->is_active)
                        <span class="badge text-bg-secondary">Unpublished</span>
                    @endif
                    @if ($job->isExpired())
                        <span class="badge text-bg-danger">Deadline passed</span>
                    @endif
                </div>

                <h1 class="h4 fw-bold mb-2">{{ $job->title }}</h1>

                <p class="text-muted mb-4">
                    <i class="bi bi-building" aria-hidden="true"></i> {{ $job->company }}
                    @if ($job->location)
                        &middot; <i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $job->location }}
                    @endif
                    &middot; posted {{ $job->created_at->diffForHumans() }}
                </p>

                <h2 class="h6 fw-bold">Description</h2>
                <div class="prose mb-4">{{ $job->description }}</div>

                @if ($job->requirements)
                    <h2 class="h6 fw-bold">Requirements</h2>
                    <div class="prose mb-4">{{ $job->requirements }}</div>
                @endif

                @if ($canManage)
                    <div class="d-flex flex-wrap gap-2 border-top pt-3">
                        <a href="{{ route('jobs.edit', $job) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                        </a>
                        <a href="{{ route('jobs.applicants', $job) }}" class="btn btn-sm btn-outline-dark">
                            <i class="bi bi-people" aria-hidden="true"></i> Applicants ({{ $job->applications_count }})
                        </a>
                        <form method="POST" action="{{ route('jobs.destroy', $job) }}"
                              onsubmit="return confirm('Delete this opportunity and all of its applications?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash" aria-hidden="true"></i> Delete
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </article>
    </div>

    <div class="col-lg-4">
        <aside class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">At a glance</h2>
                <dl class="row small mb-4">
                    <dt class="col-5 text-muted fw-normal">Salary</dt>
                    <dd class="col-7">{{ $job->salaryRange() }}</dd>

                    <dt class="col-5 text-muted fw-normal">Work mode</dt>
                    <dd class="col-7 text-capitalize">{{ $job->work_mode }}</dd>

                    <dt class="col-5 text-muted fw-normal">Deadline</dt>
                    <dd class="col-7">{{ $job->deadline?->format('d M Y') ?? 'Open until filled' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Applicants</dt>
                    <dd class="col-7">{{ $job->applications_count }}</dd>
                </dl>

                @auth
                    @if ($hasApplied)
                        <div class="alert alert-success small mb-0" role="status">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            You have already applied.
                            <a href="{{ route('applications.index') }}" class="alert-link">View my applications</a>
                        </div>
                    @elseif (! $job->isOpen())
                        <p class="small text-muted mb-0">This opportunity is closed for applications.</p>
                    @elseif ($me->id === $job->user_id)
                        <p class="small text-muted mb-0">You published this opportunity.</p>
                    @else
                        <a href="{{ route('jobs.apply', $job) }}" class="btn btn-info w-100 fw-semibold">
                            <i class="bi bi-send" aria-hidden="true"></i> Apply now
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-dark w-100">Sign in to apply</a>
                @endauth
            </div>
        </aside>

        <aside class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">Posted by</h2>
                <div class="d-flex align-items-center gap-3">
                    @include('partials.avatar', ['user' => $job->user, 'size' => 44])
                    <div class="min-w-0">
                        <a href="{{ route('profile.show', $job->user) }}"
                           class="fw-semibold text-decoration-none d-block">{{ $job->user->name }}</a>
                        <span class="badge text-bg-light border">{{ $job->user->roleName() }}</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

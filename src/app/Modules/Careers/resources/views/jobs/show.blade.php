@extends('layouts.app')
@section('title', $job->title)
@section('subtitle', 'Jobs & opportunities')

@php
    $me = auth()->user();
    $canManage = (bool) $me?->can('update', $job);
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
                    <x-org-mark :name="$job->company" size="lg" />
                    <div class="min-w-0 flex-grow-1">
                        <h1 class="aq-h1">{{ $job->title }}</h1>
                        <p class="mb-0 aq-text aq-muted">
                            @if ($job->organization)
                                <a href="{{ route('organizations.show', $job->organization) }}">{{ $job->company }}</a>
                            @else
                                {{ $job->company }}
                            @endif
                            @if ($job->location) · {{ $job->location }} @endif
                            · posted {{ $job->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @auth
                        {{-- Save for later (1.15): the Vue toggle, with the plain form as fallback. --}}
                        <x-vue name="BookmarkButton" class="aq-bookmark"
                               :props="['url' => route('jobs.save', $job), 'saved' => $saved, 'count' => $job->saved_by_count, 'label' => 'Save this opportunity']">
                            <form method="POST" action="{{ route($saved ? 'jobs.unsave' : 'jobs.save', $job) }}">
                                @csrf
                                @if ($saved) @method('DELETE') @endif
                                <button type="submit" @class(['aq-bookmark-btn', 'is-active' => $saved])
                                        aria-label="Save this opportunity" aria-pressed="{{ $saved ? 'true' : 'false' }}" title="Save this opportunity">
                                    <i class="bi bi-bookmark{{ $saved ? '-fill' : '' }}" aria-hidden="true"></i>
                                </button>
                            </form>
                        </x-vue>
                    @endauth
                </div>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="badge {{ $job->typeBadge() }}">
                        <i class="bi bi-{{ $job->typeIcon() }} me-1" aria-hidden="true"></i>
                        {{ $job->typeLabel() }}
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

                @if ($job->eligibility)
                    <h2 class="aq-label mb-2">Who can apply</h2>
                    <div class="prose mb-4">{{ $job->eligibility }}</div>
                @endif

                @if ($job->external_url)
                    <p class="mb-4">
                        <a href="{{ $job->external_url }}" target="_blank" rel="noopener noreferrer nofollow">
                            <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>More information from the organizer<span class="visually-hidden"> (opens in a new tab)</span>
                        </a>
                    </p>
                @endif

                <p class="mb-3 text-end"><x-report-link :item="$job" /></p>

                @if ($canManage)
                    <div class="d-flex flex-wrap gap-2 pt-3 aq-border-top">
                        <a href="{{ route('jobs.edit', $job) }}" class="btn btn-sm btn-quiet">
                            <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit
                        </a>
                        <a href="{{ route('jobs.applicants', $job) }}" class="btn btn-sm btn-quiet">
                            <i class="bi bi-people me-1" aria-hidden="true"></i> Applicants ({{ $job->applications_count }})
                        </a>
                        <form method="POST" action="{{ route('jobs.destroy', $job) }}"
                              data-confirm-title="Delete this opportunity?"
                              data-confirm="“{{ $job->title }}” and all of its applications are permanently deleted."
                              data-confirm-button="Delete opportunity">
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
                    <dt>Type</dt><dd>{{ $job->typeLabel() }}</dd>
                    @if ($job->moneyLabel())
                        <dt>{{ $job->moneyLabel() }}</dt><dd>{{ $job->salaryRange() }}</dd>
                    @endif
                    <dt>Work mode</dt><dd class="text-capitalize">{{ $job->work_mode }}</dd>
                    <dt>Deadline</dt><dd>{{ $job->deadline?->format('d M Y') ?? 'Open until filled' }}</dd>
                    @if ($job->dateRange())
                        <dt>Dates</dt><dd>{{ $job->dateRange() }}</dd>
                    @endif
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
                        <p class="mb-0 aq-text-sm aq-muted">This opportunity is closed for applications.</p>
                    @elseif ($me->id === $job->user_id)
                        <p class="mb-0 aq-text-sm aq-muted">You published this opportunity.</p>
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

        @if ($job->organization)
            <aside class="card mb-3">
                <div class="card-body">
                    <h2 class="aq-label mb-3">Organization</h2>
                    <div class="d-flex align-items-center gap-3">
                        <x-org-mark :name="$job->organization->name" />
                        <div class="min-w-0">
                            <a href="{{ route('organizations.show', $job->organization) }}"
                               class="fw-semibold text-decoration-none d-block">{{ $job->organization->name }}</a>
                            @if ($job->organization->tagline)
                                <span class="aq-text-xs aq-muted">{{ $job->organization->tagline }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </aside>
        @endif

        <aside class="card">
            <div class="card-body">
                <h2 class="aq-label mb-3">Posted by</h2>
                <div class="d-flex align-items-center gap-3">
                    <x-avatar :user="$job->user" size="md" />
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

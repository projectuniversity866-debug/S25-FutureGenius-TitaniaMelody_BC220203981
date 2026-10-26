@extends('layouts.app')
@section('title', $organization->name)
@section('subtitle', 'Organization')

@section('content')
<nav aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('jobs.index') }}">Jobs &amp; opportunities</a></li>
        <li class="breadcrumb-item"><a href="{{ route('organizations.index') }}">Organizations</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $organization->name }}</li>
    </ol>
</nav>

<section class="card aq-org-hero mb-4" aria-labelledby="org-name">
    <div class="card-body">
        <x-org-mark :name="$organization->name" size="xl" />
        <div class="min-w-0 flex-grow-1">
            <h1 id="org-name" class="aq-h1 mb-1">{{ $organization->name }}</h1>
            @if ($organization->tagline)
                <p class="aq-text aq-ink-2 mb-2">{{ $organization->tagline }}</p>
            @endif
            <ul class="aq-org-facts">
                @if ($organization->industry)<li><i class="bi bi-diagram-3" aria-hidden="true"></i> {{ $organization->industry }}</li>@endif
                @if ($organization->location)<li><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $organization->location }}</li>@endif
                @if ($organization->size)<li><i class="bi bi-people" aria-hidden="true"></i> {{ $organization->size }} people</li>@endif
                @if ($organization->website)
                    <li>
                        <i class="bi bi-link-45deg" aria-hidden="true"></i>
                        <a href="{{ $organization->website }}" target="_blank" rel="noopener noreferrer nofollow">{{ $organization->websiteHost() }}<span class="visually-hidden"> (opens in a new tab)</span></a>
                    </li>
                @endif
            </ul>
        </div>
        @can('update', $organization)
            <div class="aq-org-hero-actions">
                <a href="{{ route('organizations.edit', $organization) }}" class="btn btn-sm btn-quiet">
                    <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit profile
                </a>
                <a href="{{ route('jobs.create') }}" class="btn btn-sm btn-accent">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Post an opportunity
                </a>
            </div>
        @endcan
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-8">
        <section aria-labelledby="open-heading">
            <div class="aq-section-head">
                <h2 id="open-heading">Open opportunities ({{ $open->count() }})</h2>
            </div>
            @if ($open->isEmpty())
                <div class="card">
                    <x-empty icon="briefcase" title="Nothing open right now"
                             message="{{ $organization->name }} has no open opportunities at the moment." />
                </div>
            @else
                <div class="row g-3">
                    @foreach ($open as $job)
                        @include('careers::partials.job-card', ['job' => $job])
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="col-lg-4">
        <x-card title="About" icon="info-circle" class="mb-3">
            @if ($organization->about)
                <div class="prose aq-text-sm">{{ $organization->about }}</div>
            @else
                <p class="aq-text-sm aq-muted mb-0">No description yet.</p>
            @endif
        </x-card>

        <aside class="card">
            <div class="card-body">
                <h2 class="aq-label mb-3">At a glance</h2>
                <dl class="aq-dl mb-0">
                    <dt>Open now</dt><dd class="aq-num">{{ $open->count() }}</dd>
                    <dt>Closed or past</dt><dd class="aq-num">{{ $closedCount }}</dd>
                    <dt>Managed by</dt><dd><a href="{{ route('profile.show', $organization->owner) }}">{{ $organization->owner->name }}</a></dd>
                    <dt>On AQDesk since</dt><dd>{{ $organization->created_at->format('M Y') }}</dd>
                </dl>
            </div>
        </aside>

        @can('delete', $organization)
            <form method="POST" action="{{ route('organizations.destroy', $organization) }}" class="mt-3"
                  data-confirm-title="Delete “{{ $organization->name }}”?"
                  data-confirm="The profile is removed. Its opportunities stay on the board without a profile link."
                  data-confirm-button="Delete organization">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash me-1" aria-hidden="true"></i> Delete organization
                </button>
            </form>
        @endcan
    </div>
</div>
@endsection

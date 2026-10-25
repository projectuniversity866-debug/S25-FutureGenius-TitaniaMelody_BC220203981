@extends('layouts.app')
@section('title', 'Jobs & internships')
@section('subtitle', 'Openings shared by the AQDesk community')

@section('content')

<div class="aq-page-head">
    <div>
        <h1>Jobs &amp; opportunities</h1>
        <p>
            <span class="aq-num">{{ $counts['job'] }}</span> jobs and
            <span class="aq-num">{{ $counts['internship'] }}</span> internships currently open.
        </p>
    </div>
    @auth
        @if (auth()->user()->canPostJobs())
            <div class="d-flex gap-2">
                <a href="{{ route('jobs.mine') }}" class="btn btn-quiet">
                    <i class="bi bi-clipboard-check me-1" aria-hidden="true"></i> My postings
                </a>
                <a href="{{ route('jobs.create') }}" class="btn btn-accent">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Post an opportunity
                </a>
            </div>
        @endif
    @endauth
</div>

{{-- Filter toolbar --}}
<form method="GET" action="{{ route('jobs.index') }}" class="aq-toolbar">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="q" class="form-label">Search</label>
            <input type="search" id="q" name="q" class="form-control form-control-sm"
                   value="{{ request('q') }}" placeholder="Title, company or location…">
        </div>
        <div class="col-md-3">
            <label for="type" class="form-label">Type</label>
            <select id="type" name="type" class="form-select form-select-sm">
                <option value="">All types ({{ $counts['all'] }})</option>
                <option value="job" @selected(request('type') === 'job')>Jobs ({{ $counts['job'] }})</option>
                <option value="internship" @selected(request('type') === 'internship')>Internships ({{ $counts['internship'] }})</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="mode" class="form-label">Work mode</label>
            <select id="mode" name="mode" class="form-select form-select-sm">
                <option value="">Any</option>
                <option value="onsite" @selected(request('mode') === 'onsite')>On-site</option>
                <option value="remote" @selected(request('mode') === 'remote')>Remote</option>
                <option value="hybrid" @selected(request('mode') === 'hybrid')>Hybrid</option>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-accent btn-sm">Filter</button>
        </div>
    </div>
</form>

<div class="row g-3">
    @forelse ($jobs as $job)
        @include('partials.job-card', ['job' => $job])
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @include('partials.empty', [
                        'icon' => 'briefcase',
                        'title' => 'No opportunities match your filters',
                        'message' => 'Try clearing the search or choosing a different type.',
                    ])
                </div>
            </div>
        </div>
    @endforelse
</div>

<nav aria-label="Opportunity list pages" class="mt-4">
    {{ $jobs->links() }}
</nav>
@endsection

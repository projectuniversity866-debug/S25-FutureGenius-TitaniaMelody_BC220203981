@extends('layouts.app')
@section('title', 'Jobs & internships')
@section('subtitle', 'Openings shared by the AQDesk community')

@section('content')
<x-page-header title="Jobs & opportunities">
    <p>
        <span class="aq-num">{{ $counts['job'] }}</span> {{ \Illuminate\Support\Str::plural('job', $counts['job']) }} and
        <span class="aq-num">{{ $counts['internship'] }}</span> {{ \Illuminate\Support\Str::plural('internship', $counts['internship']) }} currently open.
    </p>
    @can('create', \App\Modules\Careers\Models\JobPost::class)
        <x-slot:actions>
            <a href="{{ route('jobs.mine') }}" class="btn btn-quiet">
                <i class="bi bi-clipboard-check me-1" aria-hidden="true"></i> My postings
            </a>
            <a href="{{ route('jobs.create') }}" class="btn btn-accent">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Post an opportunity
            </a>
        </x-slot:actions>
    @endcan
</x-page-header>

@php
    $type = request('type');
    $tabUrl = fn ($t) => route('jobs.index', array_filter(array_merge(request()->except('page', 'type'), ['type' => $t])));
@endphp
<x-tabs label="Opportunity type" :items="[
    ['label' => 'All', 'url' => $tabUrl(null), 'active' => ! $type, 'count' => $counts['all']],
    ['label' => 'Jobs', 'url' => $tabUrl('job'), 'active' => $type === 'job', 'count' => $counts['job']],
    ['label' => 'Internships', 'url' => $tabUrl('internship'), 'active' => $type === 'internship', 'count' => $counts['internship']],
]" />

<x-filter-panel :action="route('jobs.index')" label="Filter opportunities" :keep="['type']"
                :filters="['q' => 'Search', 'mode' => ['Work mode', ['onsite' => 'On-site', 'remote' => 'Remote', 'hybrid' => 'Hybrid']]]"
                :total="$jobs->total()" noun="opportunity">
    <div class="is-wide">
        <label for="q" class="form-label">Search</label>
        <input type="search" id="q" name="q" class="form-control form-control-sm"
               value="{{ request('q') }}" placeholder="Title, company or location…">
    </div>
    <div>
        <label for="mode" class="form-label">Work mode</label>
        <select id="mode" name="mode" class="form-select form-select-sm">
            <option value="">Any</option>
            <option value="onsite" @selected(request('mode') === 'onsite')>On-site</option>
            <option value="remote" @selected(request('mode') === 'remote')>Remote</option>
            <option value="hybrid" @selected(request('mode') === 'hybrid')>Hybrid</option>
        </select>
    </div>
</x-filter-panel>

<div class="row g-3">
    @forelse ($jobs as $job)
        @include('careers::partials.job-card', ['job' => $job])
    @empty
        <div class="col-12">
            <div class="card">
                <x-empty icon="briefcase" title="No opportunities match your filters"
                         message="Try clearing the search or choosing a different type." />
            </div>
        </div>
    @endforelse
</div>

<nav aria-label="Opportunity list pages" class="mt-4">
    {{ $jobs->links() }}
</nav>
@endsection

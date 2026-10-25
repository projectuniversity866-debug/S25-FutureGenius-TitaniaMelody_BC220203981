@extends('layouts.app')
@section('title', 'Opportunities')
@section('subtitle', 'Jobs, internships, funding and events shared by the AQDesk community')

@section('content')
@php
    $types = \App\Modules\Careers\Models\JobPost::TYPES;
    $type = is_string(request('type')) && isset($types[request('type')]) ? request('type') : null;
    $tabUrl = fn ($t) => route('jobs.index', array_filter(array_merge(request()->except('page', 'type'), ['type' => $t])));
    $tabs = [['label' => 'All', 'url' => $tabUrl(null), 'active' => ! $type, 'count' => $counts['all']]];
    foreach ($types as $key => $info) {
        $tabs[] = ['label' => $info[1], 'url' => $tabUrl($key), 'active' => $type === $key, 'count' => $counts[$key]];
    }
@endphp

<x-page-header title="Jobs & opportunities">
    <p>
        <span class="aq-num">{{ $counts['all'] }}</span> open: jobs and internships, scholarships, fellowships and grants,
        hackathons and calls for papers.
    </p>
    <x-slot:actions>
        <a href="{{ route('organizations.index') }}" class="btn btn-quiet">
            <i class="bi bi-buildings me-1" aria-hidden="true"></i> Organizations
        </a>
        @can('create', \App\Modules\Careers\Models\JobPost::class)
            <a href="{{ route('jobs.mine') }}" class="btn btn-quiet">
                <i class="bi bi-clipboard-check me-1" aria-hidden="true"></i> My postings
            </a>
            <a href="{{ route('jobs.create') }}" class="btn btn-accent">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Post an opportunity
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-tabs label="Opportunity type" :items="$tabs" />

<x-filter-panel :action="route('jobs.index')" label="Filter opportunities" :keep="['type']"
                :filters="['q' => 'Search', 'mode' => ['Work mode', ['onsite' => 'On-site', 'remote' => 'Remote', 'hybrid' => 'Hybrid']]]"
                :total="$jobs->total()" noun="opportunity">
    <div class="is-wide">
        <label for="q" class="form-label">Search</label>
        <input type="search" id="q" name="q" class="form-control form-control-sm"
               value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="Title, organization or location…">
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

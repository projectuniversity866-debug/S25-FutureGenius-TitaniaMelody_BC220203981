@extends('layouts.app')
@section('title', 'Organizations')
@section('subtitle', 'Jobs & opportunities')

@section('content')
<x-page-header title="Organizations"
               subtitle="Employers, universities and programmes that publish opportunities on AQDesk."
               :breadcrumbs="['Jobs & opportunities' => route('jobs.index'), 'Organizations' => null]">
    @can('create', \App\Modules\Careers\Models\Organization::class)
        <x-slot:actions>
            <a href="{{ route('organizations.create') }}" class="btn btn-accent">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add your organization
            </a>
        </x-slot:actions>
    @endcan
</x-page-header>

<x-filter-panel :action="route('organizations.index')" label="Find an organization"
                :filters="['q' => 'Search']" :total="$organizations->total()" noun="organization">
    <div class="is-wide">
        <label for="q" class="form-label">Search</label>
        <input type="search" id="q" name="q" class="form-control form-control-sm"
               value="{{ $search }}" placeholder="Name, industry or location…">
    </div>
</x-filter-panel>

@if ($organizations->isEmpty())
    <div class="card">
        <x-empty icon="buildings" title="No organizations found" message="Try a shorter search." />
    </div>
@else
    <ul class="aq-org-grid">
        @foreach ($organizations as $organization)
            <li>
                <a href="{{ route('organizations.show', $organization) }}" class="card aq-card-link h-100">
                    <div class="card-body d-flex gap-3">
                        <x-org-mark :name="$organization->name" size="lg" />
                        <div class="min-w-0">
                            <h2 class="aq-card-title">{{ $organization->name }}</h2>
                            @if ($organization->tagline)
                                <p class="aq-text-sm aq-ink-2 mb-1">{{ $organization->tagline }}</p>
                            @endif
                            <p class="aq-text-xs aq-muted mb-0">
                                {{ collect([$organization->industry, $organization->location])->filter()->implode(' · ') }}
                            </p>
                            <span @class(['badge mt-2', 'text-bg-success' => $organization->open_count, 'text-bg-light' => ! $organization->open_count])>
                                {{ $organization->open_count }} open {{ \Illuminate\Support\Str::plural('opportunity', $organization->open_count) }}
                            </span>
                        </div>
                    </div>
                </a>
            </li>
        @endforeach
    </ul>

    <nav aria-label="Organization list pages" class="mt-4">{{ $organizations->links() }}</nav>
@endif
@endsection

@extends('layouts.app')
@section('title', 'Projects')
@section('subtitle', 'Project collaboration')

@section('content')
<x-page-header title="Projects" subtitle="Shared spaces where members work together on a project.">
    <x-slot:actions>
        <a href="{{ route('projects.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New project</a>
    </x-slot:actions>
</x-page-header>

<x-tabs label="Projects" :items="[
    ['label' => 'My projects', 'url' => route('projects.index'), 'active' => $tab === 'mine', 'count' => $counts['mine']],
    ['label' => 'Discover', 'url' => route('projects.index', ['tab' => 'discover']), 'active' => $tab === 'discover', 'count' => $counts['discover']],
]" />

@if ($projects->isEmpty())
    <div class="card">
        @if ($tab === 'mine')
            <x-empty icon="kanban" title="You are not in any project yet"
                     message="Create one for your final-year project or study group, or discover projects open to members."
                     :action="['url' => route('projects.create'), 'label' => 'Create a project']" />
        @else
            <x-empty icon="compass" title="Nothing to discover right now" message="Projects that owners open to all members appear here." />
        @endif
    </div>
@else
    <div class="row g-3">
        @foreach ($projects as $project)
            <div class="col-md-6 col-xl-4">
                <article class="card h-100 aq-article-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            @if ($tab === 'mine')
                                <span class="badge text-bg-primary">{{ \App\Modules\Projects\Models\Project::ROLES[$project->pivot->role][0] ?? '' }}</span>
                            @endif
                            <span class="badge text-bg-light">{{ \App\Modules\Projects\Models\Project::VISIBILITY[$project->visibility][0] }}</span>
                            @if ($project->isArchived())<span class="badge text-bg-secondary">Archived</span>@endif
                        </div>
                        <h2 class="aq-card-title"><a href="{{ route('projects.show', $project) }}" class="stretched-link text-decoration-none">{{ $project->name }}</a></h2>
                        <p class="aq-text-sm aq-ink-2 flex-grow-1">{{ \Illuminate\Support\Str::limit($project->description, 140) }}</p>
                        <p class="mb-0 aq-text-xs aq-muted">
                            {{ $project->members_count }} {{ \Illuminate\Support\Str::plural('member', $project->members_count) }} · owner {{ $project->owner->name }}
                        </p>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
    <nav aria-label="Project pages" class="mt-4">{{ $projects->links() }}</nav>
@endif
@endsection

@extends('layouts.app')
@section('title', 'Resource library')
@section('subtitle', 'Learning Hub')

@section('content')
@php use App\Modules\Knowledge\Models\LearningResource as R; @endphp
<x-page-header title="Resource library" subtitle="Videos, courses, tutorials, books and tools recommended by verified experts."
               :breadcrumbs="['Learning Hub' => route('learning.index'), 'Resource library' => null]">
    @can('create', R::class)
        <x-slot:actions>
            <a href="{{ route('learning.resources.create') }}" class="btn btn-accent">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add a resource
            </a>
        </x-slot:actions>
    @endcan
</x-page-header>

<x-filter-panel :action="route('learning.resources')" label="Filter resources"
                :filters="[
                    'q' => 'Search',
                    'category' => ['Topic', $categories->pluck('name', 'slug')->all()],
                    'kind' => ['Kind', collect(R::KINDS)->map(fn ($k) => $k[0])->all()],
                    'level' => ['Level', R::LEVELS],
                    'free' => ['Price', ['1' => 'Free only']],
                ]"
                :total="$resources->total()" noun="resource">
    <div class="is-wide">
        <label for="q" class="form-label">Search</label>
        <input type="search" id="q" name="q" class="form-control form-control-sm" value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="Title, provider or topic…">
    </div>
    <div>
        <label for="category" class="form-label">Topic</label>
        <select id="category" name="category" class="form-select form-select-sm">
            <option value="">Any</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->slug }}" @selected(request('category') === $cat->slug)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="kind" class="form-label">Kind</label>
        <select id="kind" name="kind" class="form-select form-select-sm">
            <option value="">Any</option>
            @foreach (R::KINDS as $key => [$label])
                <option value="{{ $key }}" @selected(request('kind') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="level" class="form-label">Level</label>
        <select id="level" name="level" class="form-select form-select-sm">
            <option value="">Any</option>
            @foreach (R::LEVELS as $key => $label)
                <option value="{{ $key }}" @selected(request('level') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-check align-self-end mb-1">
        <input class="form-check-input" type="checkbox" id="free" name="free" value="1" @checked(request('free') === '1')>
        <label class="form-check-label" for="free">Free only</label>
    </div>
</x-filter-panel>

@if ($resources->isEmpty())
    <div class="card"><x-empty icon="collection" title="No resources match" message="Try fewer filters." /></div>
@else
    <h2 class="visually-hidden">{{ $resources->total() }} {{ \Illuminate\Support\Str::plural('resource', $resources->total()) }}</h2>
    <ul class="aq-resource-list">
        @foreach ($resources as $resource)
            @include('knowledge::learning._resource', ['resource' => $resource])
        @endforeach
    </ul>
    <nav aria-label="Resource pages" class="mt-4">{{ $resources->links() }}</nav>
@endif
@endsection

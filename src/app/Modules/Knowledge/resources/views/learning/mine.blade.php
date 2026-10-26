@extends('layouts.app')
@section('title', 'My learning')
@section('subtitle', 'Learning Hub')

@section('content')
<x-page-header title="My learning" subtitle="Paths you are following, and what you curate."
               :breadcrumbs="['Learning Hub' => route('learning.index'), 'My learning' => null]" />

<x-card title="In progress" icon="signpost-2" class="mb-4" flush>
    <ul class="list-group list-group-flush">
        @forelse ($started as $item)
            <li class="list-group-item">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <a href="{{ route('learning.paths.show', $item['path']) }}" class="fw-semibold flex-grow-1">{{ $item['path']->title }}</a>
                    <span class="aq-text-sm aq-muted">{{ $item['done'] }} of {{ $item['total'] }} steps</span>
                </div>
                <progress class="aq-progress mt-2" max="{{ max(1, $item['total']) }}" value="{{ $item['done'] }}"
                          aria-label="Progress on {{ $item['path']->title }}">{{ $item['done'] }} of {{ $item['total'] }}</progress>
            </li>
        @empty
            <li class="list-group-item">
                <x-empty icon="signpost-2" title="You have not started a path yet"
                         :action="['url' => route('learning.index'), 'label' => 'Browse learning paths']" />
            </li>
        @endforelse
    </ul>
</x-card>

@if ($paths->isNotEmpty() || $resources->isNotEmpty())
    <div class="row g-4">
        <div class="col-lg-6">
            <x-card title="Paths I curate" icon="list-ol" flush>
                <ul class="list-group list-group-flush">
                    @forelse ($paths as $path)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <a href="{{ route('learning.paths.edit', $path) }}" class="flex-grow-1">{{ $path->title }}</a>
                            <span class="aq-text-xs aq-muted">{{ $path->steps_count }} steps</span>
                            <x-status :value="$path->isPublished() ? 'published' : 'unpublished'" />
                        </li>
                    @empty
                        <li class="list-group-item aq-text-sm aq-muted">None yet.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
        <div class="col-lg-6">
            <x-card title="Resources I added" icon="collection" flush>
                <ul class="list-group list-group-flush">
                    @forelse ($resources as $resource)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <span class="flex-grow-1">{{ $resource->title }}</span>
                            <a href="{{ route('learning.resources.edit', $resource) }}" class="aq-text-sm">Edit<span class="visually-hidden"> {{ $resource->title }}</span></a>
                        </li>
                    @empty
                        <li class="list-group-item aq-text-sm aq-muted">None yet.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
@endif
@endsection

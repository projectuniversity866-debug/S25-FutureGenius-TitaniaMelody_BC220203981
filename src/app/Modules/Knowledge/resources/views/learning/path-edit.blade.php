@extends('layouts.app')
@section('title', 'Edit: '.$path->title)
@section('subtitle', 'Learning Hub')

@section('content')
<x-page-header :title="$path->title" :subtitle="$path->isPublished() ? 'Published — learners can follow it.' : 'Draft — add steps, then publish.'"
               :breadcrumbs="['Learning Hub' => route('learning.index'), $path->title => route('learning.paths.show', $path), 'Edit' => null]">
    <x-slot:actions><x-status :value="$path->isPublished() ? 'published' : 'unpublished'" /></x-slot:actions>
</x-page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <x-card title="Steps ({{ $path->steps->count() }})" icon="list-ol" id="steps" class="mb-4">
            @if ($path->steps->isEmpty())
                <p class="aq-text-sm aq-muted">No steps yet. Add resources from the library below, in the order learners should follow.</p>
            @else
                <ol class="aq-step-builder">
                    @foreach ($path->steps as $i => $step)
                        <li>
                            <span class="aq-pipeline-dot flex-shrink-0" aria-hidden="true">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-grow-1">
                                <strong class="d-block aq-text-sm">{{ $step->resource->title }}</strong>
                                <span class="aq-text-xs aq-muted">{{ $step->resource->kindLabel() }}{{ $step->note ? ' · '.$step->note : '' }}</span>
                            </div>
                            <div class="aq-actions flex-shrink-0">
                                @foreach (['up' => 'arrow-up', 'down' => 'arrow-down'] as $dir => $icon)
                                    <form method="POST" action="{{ route('learning.steps.move', $step) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="direction" value="{{ $dir }}">
                                        <button type="submit" class="btn btn-sm btn-quiet" @disabled(($dir === 'up' && $loop->parent->first) || ($dir === 'down' && $loop->parent->last))>
                                            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i><span class="visually-hidden">Move step {{ $i + 1 }} {{ $dir }}</span>
                                        </button>
                                    </form>
                                @endforeach
                                <form method="POST" action="{{ route('learning.steps.destroy', $step) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Remove step {{ $i + 1 }}</span>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif

            <form method="POST" action="{{ route('learning.steps.store', $path) }}" class="aq-border-top pt-3 mt-3">
                @csrf
                <h3 class="aq-label mb-2">Add a step</h3>
                @if ($resources->isEmpty())
                    <p class="aq-text-sm aq-muted mb-0">Every library resource is already in this path. <a href="{{ route('learning.resources.create') }}">Add a resource</a> first.</p>
                @else
                    <div class="row g-2">
                        <div class="col-md-7">
                            <label for="learning_resource_id" class="form-label">Resource</label>
                            <select id="learning_resource_id" name="learning_resource_id" class="form-select @error('learning_resource_id') is-invalid @enderror">
                                @foreach ($resources as $resource)
                                    <option value="{{ $resource->id }}">{{ $resource->title }} ({{ $resource->kindLabel() }})</option>
                                @endforeach
                            </select>
                            @error('learning_resource_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label for="note" class="form-label">Why this step <span class="aq-muted fw-normal">(optional)</span></label>
                            <input type="text" id="note" name="note" maxlength="300" class="form-control" placeholder="e.g. Watch the first 20 minutes">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-soft mt-2"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add step</button>
                    <a href="{{ route('learning.resources.create') }}" class="aq-text-sm ms-2">or add a new resource</a>
                @endif
            </form>
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card title="Details" icon="info-circle" class="mb-3">
            <form method="POST" action="{{ route('learning.paths.update', $path) }}" novalidate>
                @csrf
                @method('PUT')
                @include('knowledge::learning._path-fields', ['path' => $path])
                @error('publish')<div class="alert alert-danger py-2 aq-text-sm mt-3 mb-0" role="alert">{{ $message }}</div>@enderror
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" name="action" value="publish" class="btn btn-accent btn-sm">{{ $path->isPublished() ? 'Save and keep published' : 'Publish' }}</button>
                    <button type="submit" name="action" value="draft" class="btn btn-quiet btn-sm">{{ $path->isPublished() ? 'Unpublish' : 'Save draft' }}</button>
                </div>
            </form>
        </x-card>
        @can('delete', $path)
            <form method="POST" action="{{ route('learning.paths.destroy', $path) }}"
                  data-confirm-title="Delete “{{ $path->title }}”?" data-confirm="The path and everyone’s progress on it are deleted. Library resources stay."
                  data-confirm-button="Delete path">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Delete path</button>
            </form>
        @endcan
    </div>
</div>
@endsection

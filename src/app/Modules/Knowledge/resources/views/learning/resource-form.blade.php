@extends('layouts.app')
@section('title', $resource ? 'Edit resource' : 'Add a resource')
@section('subtitle', 'Learning Hub')

@section('content')
@php use App\Modules\Knowledge\Models\LearningResource as R; @endphp
<x-page-header :title="$resource ? 'Edit resource' : 'Add a resource'"
               :breadcrumbs="['Learning Hub' => route('learning.index'), 'Resource library' => route('learning.resources'), ($resource ? 'Edit' : 'Add') => null]" />

<div class="card mb-3">
    <div class="card-body p-4">
        <form method="POST" action="{{ $resource ? route('learning.resources.update', $resource) : route('learning.resources.store') }}" novalidate>
            @csrf
            @if ($resource) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $resource?->title) }}" required maxlength="255">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="provider" class="form-label">Provider <span class="aq-muted fw-normal">(optional)</span></label>
                    <input type="text" id="provider" name="provider" class="form-control" value="{{ old('provider', $resource?->provider) }}" maxlength="80" placeholder="YouTube, Coursera, MDN…">
                </div>
                <div class="col-12">
                    <label for="url" class="form-label">Link</label>
                    <input type="url" id="url" name="url" class="form-control @error('url') is-invalid @enderror" value="{{ old('url', $resource?->url) }}" required placeholder="https://…">
                    @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="kind" class="form-label">Kind</label>
                    <select id="kind" name="kind" class="form-select">
                        @foreach (R::KINDS as $key => [$label])
                            <option value="{{ $key }}" @selected(old('kind', $resource?->kind) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="level" class="form-label">Level</label>
                    <select id="level" name="level" class="form-select">
                        @foreach (R::LEVELS as $key => $label)
                            <option value="{{ $key }}" @selected(old('level', $resource?->level) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Topic</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">No topic</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) old('category_id', $resource?->category_id) === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">What will someone learn from it?</label>
                    <textarea id="description" name="description" rows="4" maxlength="2000" required class="form-control @error('description') is-invalid @enderror">{{ old('description', $resource?->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="minutes" class="form-label">Time needed (minutes) <span class="aq-muted fw-normal">(optional)</span></label>
                    <input type="number" id="minutes" name="minutes" min="1" max="10000" class="form-control @error('minutes') is-invalid @enderror" value="{{ old('minutes', $resource?->minutes) }}">
                    @error('minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="is_free" name="is_free" value="1" @checked(old('is_free', $resource?->is_free ?? true))>
                        <label class="form-check-label" for="is_free">Free to use</label>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-accent">{{ $resource ? 'Save changes' : 'Add to the library' }}</button>
                <a href="{{ route('learning.resources') }}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
    </div>
</div>

@if ($resource)
    @can('delete', $resource)
        <form method="POST" action="{{ route('learning.resources.destroy', $resource) }}"
              data-confirm-title="Remove “{{ $resource->title }}”?" data-confirm="It is removed from the library and from every learning path that uses it."
              data-confirm-button="Remove resource">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Remove resource</button>
        </form>
    @endcan
@endif
@endsection

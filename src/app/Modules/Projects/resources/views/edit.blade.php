@extends('layouts.app')
@section('title', 'Project settings')
@section('subtitle', 'Projects')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Project settings"
               :breadcrumbs="['Projects' => route('projects.index'), $project->name => route('projects.show', $project), 'Settings' => null]" />

<div class="card mb-4">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('projects.update', $project) }}" novalidate>
            @csrf
            @method('PUT')
            @include('projects::_form', ['project' => $project])
            <button type="submit" class="btn btn-accent mt-3">Save settings</button>
        </form>
    </div>
</div>

<x-card title="Archive or delete" icon="archive" class="aq-card-danger">
    <p class="aq-text-sm">Archiving makes the project read-only; you can restore it at any time. Deleting removes it for every member.</p>
    <div class="d-flex flex-wrap gap-2">
        <form method="POST" action="{{ route('projects.archive', $project) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="archived" value="{{ $project->isArchived() ? 0 : 1 }}">
            <button type="submit" class="btn btn-quiet btn-sm">{{ $project->isArchived() ? 'Restore project' : 'Archive project' }}</button>
        </form>
        <form method="POST" action="{{ route('projects.destroy', $project) }}"
              data-confirm-title="Delete “{{ $project->name }}”?" data-confirm="The project is deleted for all its members. This cannot be undone."
              data-confirm-button="Delete project">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">Delete project</button>
        </form>
    </div>
</x-card>
@endsection

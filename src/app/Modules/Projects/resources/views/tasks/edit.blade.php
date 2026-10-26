@extends('layouts.app')
@section('title', 'Edit task')
@section('subtitle', $project->name)

@section('content')
<x-page-header title="Edit task" :subtitle="$task->title"
               :breadcrumbs="['Projects' => route('projects.index'), $project->name => route('projects.show', $project), 'Tasks' => route('projects.tasks.index', $project), 'Edit' => null]">
    @can('deleteTask', [$project, $task])
        <x-slot:actions>
            <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}"
                  data-confirm-title="Delete “{{ $task->title }}”?" data-confirm="It is removed from the board for every member." data-confirm-button="Delete task">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Delete</button>
            </form>
        </x-slot:actions>
    @endcan
</x-page-header>

<div class="row">
    <div class="col-lg-7">
        <x-card>
            <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}" novalidate>
                @csrf
                @method('PUT')
                @include('projects::tasks._form')
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-accent btn-sm">Save task</button>
                    <a href="{{ route('projects.tasks.index', $project) }}#task-{{ $task->id }}" class="btn btn-quiet btn-sm">Cancel</a>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection

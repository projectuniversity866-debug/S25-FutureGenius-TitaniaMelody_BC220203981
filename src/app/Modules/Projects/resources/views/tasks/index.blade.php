@extends('layouts.app')
@section('title', 'Tasks · '.$project->name)
@section('subtitle', $project->name)

@section('content')
@php
    use App\Modules\Projects\Models\ProjectTask;
    // Buttons on a card for moving it: target status => [label, icon].
    $moves = [
        'todo'  => ['doing' => ['Start', 'play'], 'done' => ['Done', 'check2']],
        'doing' => ['todo' => ['Back to To do', 'arrow-left'], 'done' => ['Done', 'check2']],
        'done'  => ['todo' => ['Reopen', 'arrow-counterclockwise']],
    ];
@endphp
<x-page-header :title="$project->name" subtitle="Task board: what is planned, in progress and done."
               :breadcrumbs="['Projects' => route('projects.index'), $project->name => route('projects.show', $project), 'Tasks' => null]" />

@include('projects::_archived')
@include('projects::_nav', ['active' => 'tasks'])

<x-tabs label="Which tasks" :items="[
    ['label' => 'All tasks', 'url' => route('projects.tasks.index', $project), 'active' => ! $mine, 'count' => $counts['all']],
    ['label' => 'Assigned to me', 'url' => route('projects.tasks.index', [$project, 'show' => 'mine']), 'active' => $mine, 'count' => $counts['mine']],
]" />

<div class="aq-board mb-4">
    @foreach (ProjectTask::STATUSES as $status => [$columnTitle, $columnIcon])
        @php $tasks = $columns->get($status, collect()); @endphp
        <section class="aq-board-column" aria-labelledby="column-{{ $status }}">
            <h2 id="column-{{ $status }}"><i class="bi bi-{{ $columnIcon }}" aria-hidden="true"></i> {{ $columnTitle }} <span class="aq-tabs-count">{{ $tasks->count() }}</span></h2>
            @if ($tasks->isEmpty())
                <p class="aq-text-sm aq-muted mx-1 mb-1">No tasks here.</p>
            @else
                <ul class="aq-board-list">
                    @foreach ($tasks as $task)
                        @php [$pLabel, $pIcon, $pBadge] = ProjectTask::PRIORITIES[$task->priority]; @endphp
                        <li class="aq-task" id="task-{{ $task->id }}">
                            <h3>{{ $task->title }}</h3>
                            @if ($task->description)
                                <p class="aq-text-sm aq-ink-2 mb-2">{{ \Illuminate\Support\Str::limit($task->description, 160) }}</p>
                            @endif
                            <div class="aq-task-meta">
                                <span class="badge {{ $pBadge }}"><i class="bi bi-{{ $pIcon }}" aria-hidden="true"></i> {{ $pLabel }} priority</span>
                                <span><i class="bi bi-person" aria-hidden="true"></i> {{ $task->assignee?->name ?? 'Unassigned' }}</span>
                                @if ($task->due_on)
                                    @if ($task->isOverdue())
                                        <span class="aq-overdue"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Overdue · due {{ $task->due_on->format('d M') }}</span>
                                    @else
                                        <span><i class="bi bi-calendar-event" aria-hidden="true"></i> Due {{ $task->due_on->format('d M Y') }}</span>
                                    @endif
                                @endif
                                @if ($task->completed_at)
                                    <span><i class="bi bi-check2" aria-hidden="true"></i> Finished {{ $task->completed_at->diffForHumans() }}</span>
                                @endif
                            </div>
                            @can('contribute', $project)
                                <div class="aq-task-actions">
                                    @foreach ($moves[$status] as $to => [$label, $icon])
                                        <form method="POST" action="{{ route('projects.tasks.move', array_filter([$project, $task, 'show' => $mine ? 'mine' : null])) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="{{ $to }}">
                                            <button type="submit" class="btn btn-sm btn-soft">
                                                <i class="bi bi-{{ $icon }} me-1" aria-hidden="true"></i>{{ $label }}<span class="visually-hidden">: {{ $task->title }}</span>
                                            </button>
                                        </form>
                                    @endforeach
                                    <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="btn btn-sm btn-quiet">Edit<span class="visually-hidden"> {{ $task->title }}</span></a>
                                </div>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endforeach
</div>

@can('contribute', $project)
    <div class="row">
        <div class="col-lg-7">
            <x-card title="Add a task" icon="plus-circle" id="new-task">
                <form method="POST" action="{{ route('projects.tasks.store', $project) }}" novalidate>
                    @csrf
                    @include('projects::tasks._form', ['task' => null])
                    <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add to To do</button>
                    <p class="aq-text-xs aq-muted mt-2 mb-0">Tasks can be given to the owner or a collaborator; they are notified.</p>
                </form>
            </x-card>
        </div>
    </div>
@else
    <p class="aq-text-sm aq-muted">{{ $project->isArchived() ? 'The project is archived, so tasks cannot change.' : 'Viewers can follow the board but not change it.' }}</p>
@endcan
@endsection

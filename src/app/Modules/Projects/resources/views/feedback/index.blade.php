@extends('layouts.app')
@section('title', 'Feedback · '.$project->name)
@section('subtitle', $project->name)

@section('content')
<x-page-header :title="$project->name" subtitle="Feedback threads: comments, review notes and questions about the work."
               :breadcrumbs="['Projects' => route('projects.index'), $project->name => route('projects.show', $project), 'Feedback' => null]" />

@include('projects::_archived')
@include('projects::_nav', ['active' => 'feedback'])

<div class="row g-4">
    <div class="col-lg-7">
        <x-tabs label="Thread status" :items="[
            ['label' => 'Open', 'url' => route('projects.threads.index', $project), 'active' => $tab === 'open', 'count' => (int) ($counts['open'] ?? 0)],
            ['label' => 'Resolved', 'url' => route('projects.threads.index', [$project, 'tab' => 'resolved']), 'active' => $tab === 'resolved', 'count' => (int) ($counts['resolved'] ?? 0)],
        ]" />

        @if ($threads->isEmpty())
            <div class="card">
                <x-empty icon="chat-left-text" :title="$tab === 'open' ? 'No open feedback' : 'Nothing resolved yet'"
                         :message="$tab === 'open' ? 'Start a thread to review a draft, ask a question or note a problem.' : 'Threads marked as resolved are kept here.'" />
            </div>
        @else
            <div class="card">
                <ul class="list-group list-group-flush">
                    @foreach ($threads as $thread)
                        <li class="list-group-item">
                            <a href="{{ route('projects.threads.show', [$project, $thread]) }}" class="fw-semibold text-decoration-none">{{ $thread->title }}</a>
                            <span class="d-block aq-text-xs aq-muted">
                                {{ $thread->author?->name ?? 'a former member' }}
                                @if ($thread->file) · <i class="bi bi-file-earmark-text" aria-hidden="true"></i> about {{ $thread->file->name }}@endif
                                · {{ $thread->replies_count }} {{ \Illuminate\Support\Str::plural('reply', $thread->replies_count) }}
                                · active {{ $thread->last_activity_at?->diffForHumans() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <nav aria-label="Feedback pages" class="mt-3">{{ $threads->links() }}</nav>
        @endif
    </div>

    <div class="col-lg-5">
        @can('discuss', $project)
            <x-card title="Start a thread" icon="plus-circle" id="new-thread">
                <form method="POST" action="{{ route('projects.threads.store', $project) }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}" maxlength="150" required
                               class="form-control @error('title') is-invalid @enderror" @error('title') aria-describedby="title_error" @enderror>
                        @error('title')<div id="title_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if ($files->isNotEmpty())
                        <div class="mb-3">
                            <label for="project_file_id" class="form-label">About a file <span class="aq-muted fw-normal">(optional)</span></label>
                            <select id="project_file_id" name="project_file_id" class="form-select @error('project_file_id') is-invalid @enderror">
                                <option value="">The project in general</option>
                                @foreach ($files as $file)
                                    <option value="{{ $file->id }}" @selected((int) old('project_file_id', $aboutFile) === $file->id)>{{ $file->name }}</option>
                                @endforeach
                            </select>
                            @error('project_file_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    <div class="mb-3">
                        <label for="body" class="form-label">Your feedback</label>
                        <textarea id="body" name="body" rows="6" maxlength="10000" required aria-describedby="body_hint @error('body') body_error @enderror"
                                  class="form-control @error('body') is-invalid @enderror">{{ old('body') }}</textarea>
                        <div id="body_hint" class="form-text">Markdown works: **bold**, lists, `code`.</div>
                        @error('body')<div id="body_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-send me-1" aria-hidden="true"></i> Post feedback</button>
                    <p class="aq-text-xs aq-muted mt-2 mb-0">Every member of the project is notified.</p>
                </form>
            </x-card>
        @endcan
    </div>
</div>
@endsection

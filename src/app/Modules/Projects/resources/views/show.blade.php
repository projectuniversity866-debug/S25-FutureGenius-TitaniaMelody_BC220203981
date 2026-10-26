@extends('layouts.app')
@section('title', $project->name)
@section('subtitle', 'Project')

@section('content')
@php use App\Modules\Projects\Models\Project; @endphp
<x-page-header :title="$project->name" :subtitle="Project::VISIBILITY[$project->visibility][0].' project · owner '.$project->owner->name"
               :breadcrumbs="['Projects' => route('projects.index'), $project->name => null]">
    <x-slot:actions>
        @if ($role)
            <span class="badge text-bg-primary align-self-center">You: {{ Project::ROLES[$role][0] }}</span>
        @endif
        @can('update', $project)
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-quiet btn-sm"><i class="bi bi-gear me-1" aria-hidden="true"></i> Settings</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@include('projects::_archived')
@include('projects::_nav', ['active' => 'overview'])

<div class="row g-4">
    <div class="col-lg-7">
        <x-card title="About" icon="info-circle" class="mb-4">
            @if ($project->description)
                <div class="prose aq-text">{{ $project->description }}</div>
            @else
                <p class="aq-text-sm aq-muted mb-0">No description yet.</p>
            @endif
        </x-card>

        <x-card title="Files ({{ $files->count() }})" icon="folder2-open" id="files" flush>
            <ul class="list-group list-group-flush">
                @forelse ($files as $file)
                    @php $latest = $file->latestVersion; @endphp
                    <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
                        <i class="bi bi-file-earmark-text aq-muted" aria-hidden="true"></i>
                        <div class="min-w-0 flex-grow-1">
                            <a href="{{ route('projects.files.show', [$project, $file]) }}" class="fw-semibold text-decoration-none">{{ $file->name }}</a>
                            <span class="d-block aq-text-xs aq-muted">
                                v{{ $file->versions_count }} · {{ $latest?->readableSize() }} · {{ $latest?->uploader?->name ?? 'a former member' }} · {{ $file->updated_at->diffForHumans() }}
                            </span>
                        </div>
                        @if ($latest)
                            <a href="{{ route('projects.files.download', [$project, $file, $latest]) }}" class="btn btn-sm btn-quiet">
                                <i class="bi bi-download" aria-hidden="true"></i><span class="visually-hidden">Download {{ $file->name }}</span>
                            </a>
                        @endif
                    </li>
                @empty
                    <li class="list-group-item aq-text-sm aq-muted">No files yet.</li>
                @endforelse
            </ul>
            @can('contribute', $project)
                <form method="POST" action="{{ route('projects.files.store', $project) }}" enctype="multipart/form-data" class="card-body aq-border-top" novalidate>
                    @csrf
                    <h3 class="aq-label mb-2">Upload a file</h3>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="file" class="form-label">File <span class="aq-muted fw-normal">(up to 10 MB)</span></label>
                            <input type="file" id="file" name="file" class="form-control form-control-sm @error('file') is-invalid @enderror">
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="file_name" class="form-label">Name <span class="aq-muted fw-normal">(optional)</span></label>
                            <input type="text" id="file_name" name="name" maxlength="255" class="form-control form-control-sm" placeholder="Uses the file’s own name">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-soft btn-sm mt-2"><i class="bi bi-upload me-1" aria-hidden="true"></i> Upload</button>
                    <p class="aq-text-xs aq-muted mt-2 mb-0">To update a file, open it and upload a new version — earlier versions are kept.</p>
                </form>
            @endcan
        </x-card>
    </div>

    <div class="col-lg-5">
        @php $totalTasks = $taskCounts->sum(); $doneTasks = (int) ($taskCounts['done'] ?? 0); @endphp
        <x-card title="Progress" icon="kanban" class="mb-4">
            @if ($totalTasks)
                <label for="task_progress" class="aq-text-sm d-block mb-1">{{ $doneTasks }} of {{ $totalTasks }} {{ \Illuminate\Support\Str::plural('task', $totalTasks) }} done</label>
                <progress id="task_progress" class="aq-progress w-100" max="{{ $totalTasks }}" value="{{ $doneTasks }}">{{ round($doneTasks / $totalTasks * 100) }}%</progress>
                <p class="aq-text-xs aq-muted mt-1 mb-2">
                    @foreach (\App\Modules\Projects\Models\ProjectTask::STATUSES as $status => [$label]) {{ $label }}: {{ (int) ($taskCounts[$status] ?? 0) }}@if (! $loop->last) · @endif @endforeach
                </p>
            @else
                <p class="aq-text-sm aq-muted mb-2">No tasks yet.</p>
            @endif
            <a href="{{ route('projects.tasks.index', $project) }}" class="btn btn-soft btn-sm">Open the task board</a>

            <h3 class="aq-label mt-3 mb-2">Open feedback</h3>
            @forelse ($threads as $thread)
                <a href="{{ route('projects.threads.show', [$project, $thread]) }}" class="d-block text-decoration-none aq-text-sm text-truncate">{{ $thread->title }}</a>
                <span class="d-block aq-text-xs aq-muted mb-1">{{ $thread->author?->name ?? 'a former member' }} · {{ $thread->replies_count }} {{ \Illuminate\Support\Str::plural('reply', $thread->replies_count) }}</span>
            @empty
                <p class="aq-text-sm aq-muted mb-0">Nothing waiting. <a href="{{ route('projects.threads.index', $project) }}">Feedback</a></p>
            @endforelse
        </x-card>

        <x-card title="Members ({{ $project->members->count() }})" icon="people" id="members">
            <ul class="aq-member-list">
                @foreach ($project->members as $member)
                    <li>
                        <x-avatar :user="$member" size="sm" />
                        <div class="min-w-0 flex-grow-1">
                            <a href="{{ route('profile.show', $member) }}" class="fw-semibold text-decoration-none d-block text-truncate">{{ $member->name }}</a>
                            <span class="aq-text-xs aq-muted">{{ Project::ROLES[$member->pivot->role][0] }}</span>
                        </div>
                        @can('manageMembers', $project)
                            @if ($member->pivot->role !== 'owner')
                                <form method="POST" action="{{ route('projects.members.update', [$project, $member]) }}" class="d-flex gap-1">
                                    @csrf
                                    @method('PUT')
                                    <label for="role_{{ $member->id }}" class="visually-hidden">Role of {{ $member->name }}</label>
                                    <select id="role_{{ $member->id }}" name="role" class="form-select form-select-sm">
                                        @foreach (['collaborator', 'viewer'] as $r)
                                            <option value="{{ $r }}" @selected($member->pivot->role === $r)>{{ Project::ROLES[$r][0] }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-quiet btn-sm">Save<span class="visually-hidden"> role of {{ $member->name }}</span></button>
                                </form>
                                <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}"
                                      data-confirm-title="Remove {{ $member->name }}?" data-confirm="They lose access to “{{ $project->name }}”." data-confirm-button="Remove member">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Remove {{ $member->name }}</span>
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </li>
                @endforeach
            </ul>

            @can('manageMembers', $project)
                @if ($invitations->isNotEmpty())
                    <div class="aq-border-top pt-3 mt-2">
                        <h3 class="aq-label mb-2">Invitations waiting</h3>
                        <ul class="aq-member-list">
                            @foreach ($invitations as $invitation)
                                <li>
                                    <i class="bi bi-envelope aq-muted" aria-hidden="true"></i>
                                    <div class="min-w-0 flex-grow-1">
                                        <span class="d-block text-truncate aq-text-sm">{{ $invitation->email }}</span>
                                        <span class="aq-text-xs {{ $invitation->isExpired() ? 'aq-danger' : 'aq-muted' }}">
                                            {{ Project::ROLES[$invitation->role][0] }} ·
                                            {{ $invitation->isExpired() ? 'expired '.$invitation->expires_at->diffForHumans() : 'expires '.$invitation->expires_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    <form method="POST" action="{{ route('projects.invitations.resend', [$project, $invitation]) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-quiet btn-sm">Resend<span class="visually-hidden"> to {{ $invitation->email }}</span></button>
                                    </form>
                                    <form method="POST" action="{{ route('projects.invitations.cancel', [$project, $invitation]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Cancel the invitation to {{ $invitation->email }}</span>
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('projects.invitations.store', $project) }}" class="aq-border-top pt-3 mt-2" novalidate>
                    @csrf
                    <h3 class="aq-label mb-2">Invite someone</h3>
                    <div class="row g-2">
                        <div class="col-sm-7">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control form-control-sm @error('email') is-invalid @enderror">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-5">
                            <label for="new_role" class="form-label">Role</label>
                            <select id="new_role" name="role" class="form-select form-select-sm">
                                <option value="collaborator">Collaborator</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-soft btn-sm mt-2"><i class="bi bi-envelope-plus me-1" aria-hidden="true"></i> Send invitation</button>
                    <p class="aq-text-xs aq-muted mt-2 mb-0">
                        @foreach (Project::ROLES as [$label, $what]) <strong>{{ $label }}:</strong> {{ $what }}.@endforeach
                    </p>
                </form>
            @endcan

            @can('leave', $project)
                <form method="POST" action="{{ route('projects.leave', $project) }}" class="aq-border-top pt-3 mt-2"
                      data-confirm-title="Leave “{{ $project->name }}”?" data-confirm="You lose access until a member adds you again." data-confirm-button="Leave project">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-quiet btn-sm">Leave project</button>
                </form>
            @endcan
        </x-card>
    </div>
</div>
@endsection

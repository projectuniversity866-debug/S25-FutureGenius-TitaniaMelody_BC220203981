@extends('layouts.app')
@section('title', 'Application — '.$application->jobPost->title)
@section('subtitle', 'Jobs & opportunities')

@use('App\Modules\Careers\Models\JobApplication')

@php
    $job = $application->jobPost;
    $rejected = $application->status === 'rejected';
    // Where it is on the forward path; for a rejection, the last stage it reached.
    $current = $rejected
        ? ($application->statusChanges->pluck('to_status')->map(fn ($s) => array_search($s, JobApplication::STAGES, true))->filter(fn ($i) => $i !== false)->max() ?? 0)
        : array_search($application->status, JobApplication::STAGES, true);
    $crumbs = $isReviewer
        ? ['My postings' => route('jobs.mine'), $job->title => route('jobs.show', $job), 'Applicants' => route('jobs.applicants', $job), $application->user->name => null]
        : ['My applications' => route('applications.index'), $job->title => null];
@endphp

@section('content')
<x-page-header :title="$isReviewer ? $application->user->name : $job->title"
               :subtitle="$isReviewer ? 'Application for '.$job->title.' at '.$job->company : $job->company.' · applied '.$application->created_at->format('d M Y')"
               :breadcrumbs="$crumbs">
    <x-slot:actions><x-status :value="$application->status" /></x-slot:actions>
</x-page-header>

{{-- Progress: the forward stages, with a rejection shown where it happened. --}}
<section class="card mb-4" aria-labelledby="progress-heading">
    <div class="card-body">
        <h2 id="progress-heading" class="aq-label mb-3">Progress</h2>
        <ol class="aq-pipeline">
            @foreach (JobApplication::STAGES as $i => $stage)
                @php
                    $state = $rejected
                        ? ($i <= $current ? 'done' : 'skipped')
                        : ($i < $current ? 'done' : ($i === $current ? 'current' : 'todo'));
                @endphp
                <li class="is-{{ $state }}" @if ($state === 'current') aria-current="step" @endif>
                    <span class="aq-pipeline-dot" aria-hidden="true">
                        @if ($state === 'done')<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif
                    </span>
                    <span class="aq-pipeline-label">{{ ucfirst($stage) }}</span>
                    <span class="visually-hidden">— {{ ['done' => 'completed', 'current' => 'current step', 'todo' => 'not yet', 'skipped' => 'not reached'][$state] }}</span>
                </li>
            @endforeach
        </ol>
        <p class="mb-0 mt-3 aq-text-sm {{ $rejected ? 'aq-danger' : 'aq-muted' }}">
            @if ($rejected)<i class="bi bi-x-circle me-1" aria-hidden="true"></i>@endif
            {{ JobApplication::STATUS_TEXT[$application->status] }}
        </p>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-7">
        @if ($isReviewer)
            <x-card title="Applicant" icon="person" class="mb-4">
                <div class="d-flex align-items-center gap-3">
                    <x-avatar :user="$application->user" size="md" />
                    <div class="min-w-0">
                        <a href="{{ route('profile.show', $application->user) }}" class="fw-semibold text-decoration-none d-block">{{ $application->user->name }}</a>
                        <span class="aq-text-xs aq-muted">{{ $application->user->profile?->headline ?? $application->user->roleName() }}</span>
                    </div>
                    @if ($application->resume_path)
                        <a href="{{ route('applications.resume', $application) }}" class="btn btn-sm btn-quiet ms-auto">
                            <i class="bi bi-download me-1" aria-hidden="true"></i> CV
                        </a>
                    @endif
                </div>
            </x-card>
        @endif

        <x-card title="Cover letter" icon="file-text" class="mb-4">
            <div class="prose aq-text">{{ $application->cover_letter }}</div>
            @if (! $isReviewer && $application->resume_path)
                <a href="{{ route('applications.resume', $application) }}" class="btn btn-sm btn-quiet mt-3">
                    <i class="bi bi-download me-1" aria-hidden="true"></i> My CV
                </a>
            @endif
        </x-card>

        <x-card title="History" icon="clock-history">
            <ol class="aq-timeline">
                @foreach ($application->statusChanges->reverse() as $change)
                    <li>
                        <p class="mb-0 aq-text-sm">
                            @if ($change->from_status === null)
                                <strong>Submitted</strong>
                            @else
                                Moved to <x-status :value="$change->to_status" />
                                <span class="aq-muted">from {{ $change->from_status }}</span>
                            @endif
                        </p>
                        @if ($change->message)
                            <blockquote class="aq-timeline-message">{{ $change->message }}</blockquote>
                        @endif
                        <p class="mb-0 aq-text-xs aq-muted">
                            <time datetime="{{ $change->created_at->toIso8601String() }}">{{ $change->created_at->format('d M Y, H:i') }}</time>
                            @if ($change->user && $change->from_status !== null && $isReviewer) · by {{ $change->user->name }} @endif
                        </p>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>

    <div class="col-lg-5">
        @if ($isReviewer)
            <x-card title="Update status" icon="arrow-right-circle" class="mb-4">
                <form method="POST" action="{{ route('applications.status', $application) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            @foreach (JobApplication::STATUSES as $status)
                                <option value="{{ $status }}" @selected($application->status === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message to the applicant <span class="aq-muted fw-normal">(optional)</span></label>
                        <textarea id="message" name="message" rows="3" maxlength="1000"
                                  class="form-control @error('message') is-invalid @enderror"
                                  aria-describedby="message_hint">{{ old('message') }}</textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="message_hint" class="form-text">Shown in their history and notification, e.g. the interview date.</div>
                    </div>
                    <button type="submit" class="btn btn-accent btn-sm">Save status</button>
                </form>
            </x-card>

            <x-card title="Private notes" icon="lock" id="notes">
                <p class="aq-text-xs aq-muted">Only reviewers of this opportunity see these. The applicant never does.</p>
                <form method="POST" action="{{ route('applications.notes.store', $application) }}" class="mb-3">
                    @csrf
                    <label for="body" class="visually-hidden">New note</label>
                    <textarea id="body" name="body" rows="3" maxlength="2000" required
                              class="form-control @error('body') is-invalid @enderror"
                              placeholder="e.g. Strong portfolio; ask about availability">{{ old('body') }}</textarea>
                    @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-quiet btn-sm mt-2">Add note</button>
                </form>
                <ul class="aq-notes">
                    @forelse ($notes as $note)
                        <li>
                            <div class="prose aq-text-sm">{{ $note->body }}</div>
                            <p class="mb-0 aq-text-xs aq-muted">
                                {{ $note->user?->name ?? 'Former reviewer' }} · {{ $note->created_at->diffForHumans() }}
                                @can('deleteNote', [JobApplication::class, $note])
                                    <form method="POST" action="{{ route('applications.notes.destroy', $note) }}" class="d-inline"
                                          data-confirm-title="Delete this note?" data-confirm="The note is permanently deleted." data-confirm-button="Delete note">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="aq-comment-delete">Delete<span class="visually-hidden"> note</span></button>
                                    </form>
                                @endcan
                            </p>
                        </li>
                    @empty
                        <li class="is-empty aq-text-sm aq-muted">No notes yet.</li>
                    @endforelse
                </ul>
            </x-card>
        @else
            <x-card title="The opportunity" icon="briefcase" class="mb-4">
                <p class="mb-1"><a href="{{ route('jobs.show', $job) }}" class="fw-semibold">{{ $job->title }}</a></p>
                <p class="aq-text-sm aq-muted mb-3">{{ $job->typeLabel() }} · {{ $job->company }}</p>
                <form method="POST" action="{{ route('applications.destroy', $application) }}"
                      data-confirm-title="Withdraw your application?"
                      data-confirm="Your application and any uploaded CV are deleted. You can apply again while the opportunity is open."
                      data-confirm-button="Withdraw application">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Withdraw application</button>
                </form>
            </x-card>
        @endif
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Applicants — '.$job->title)
@section('subtitle', 'Jobs & opportunities')

@section('content')
<x-page-header title="Applicants" :subtitle="$job->title.' at '.$job->company"
               :breadcrumbs="['My postings' => route('jobs.mine'), $job->title => route('jobs.show', $job), 'Applicants' => null]" />

@php
    $tabs = [['label' => 'All', 'url' => route('jobs.applicants', $job), 'active' => ! $status, 'count' => $statusCounts->sum()]];
    foreach (\App\Modules\Careers\Models\JobApplication::STATUSES as $s) {
        $tabs[] = [
            'label'  => ucfirst($s),
            'url'    => route('jobs.applicants', [$job, 'status' => $s]),
            'active' => $status === $s,
            'count'  => $statusCounts[$s] ?? 0,
        ];
    }
@endphp
<x-tabs label="Filter applicants by status" :items="$tabs" />


@forelse ($applications as $application)
    <article class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <x-avatar :user="$application->user" size="md" />
                    <div class="min-w-0">
                        <a href="{{ route('profile.show', $application->user) }}"
                           class="fw-semibold text-decoration-none d-block">{{ $application->user->name }}</a>
                        <span class="aq-text-xs aq-muted">
                            {{ $application->user->profile?->headline ?? $application->user->roleName() }}
                            · applied {{ $application->created_at->diffForHumans() }}
                        </span>
                        <div class="mt-1"><x-status :value="$application->status" /></div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('applications.show', $application) }}" class="btn btn-sm btn-soft">
                        <i class="bi bi-kanban me-1" aria-hidden="true"></i> Review<span class="visually-hidden"> {{ $application->user->name }}’s application</span>
                        @if ($application->notes_count)<span class="aq-tabs-count ms-1">{{ $application->notes_count }}<span class="visually-hidden"> {{ \Illuminate\Support\Str::plural('note', $application->notes_count) }}</span></span>@endif
                    </a>
                    @if ($application->resume_path)
                        <a href="{{ route('applications.resume', $application) }}" class="btn btn-sm btn-quiet">
                            <i class="bi bi-download me-1" aria-hidden="true"></i> CV<span class="visually-hidden"> of {{ $application->user->name }}</span>
                        </a>
                    @endif

                    <form method="POST" action="{{ route('applications.status', $application) }}"
                          class="d-flex align-items-center gap-2">
                        @csrf
                        @method('PUT')
                        <label for="status_{{ $application->id }}" class="visually-hidden">
                            Application status for {{ $application->user->name }}
                        </label>
                        <select id="status_{{ $application->id }}" name="status" class="form-select form-select-sm">
                            @foreach (\App\Modules\Careers\Models\JobApplication::STATUSES as $status)
                                <option value="{{ $status }}" @selected($application->status === $status)>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-accent">Update</button>
                    </form>
                </div>
            </div>

            <div class="mt-3 pt-3 aq-border-top">
                <h2 class="aq-label mb-2">Cover letter</h2>
                <div class="prose mb-0 aq-text">{{ $application->cover_letter }}</div>
            </div>
        </div>
    </article>
@empty
    <div class="card">
        <div class="card-body">
            @if ($status)
                <x-empty icon="people" title="No {{ $status }} applicants"
                         :action="['url' => route('jobs.applicants', $job), 'label' => 'Show all applicants']" />
            @else
                <x-empty icon="people" title="No applications yet" message="Applicants will appear here as soon as they apply." />
            @endif
        </div>
    </div>
@endforelse

<nav aria-label="Applicant list pages" class="mt-4">
    {{ $applications->links() }}
</nav>
@endsection

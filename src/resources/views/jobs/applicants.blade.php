@extends('layouts.app')
@section('title', 'Applicants — '.$job->title)

@section('content')
<nav aria-label="Breadcrumb">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="{{ route('jobs.mine') }}">My postings</a></li>
        <li class="breadcrumb-item"><a href="{{ route('jobs.show', $job) }}">{{ $job->title }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Applicants</li>
    </ol>
</nav>

<h1 class="h4 fw-bold mb-1">Applicants</h1>
<p class="text-muted mb-4">{{ $job->title }} at {{ $job->company }}</p>

@forelse ($applications as $application)
    <article class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    @include('partials.avatar', ['user' => $application->user, 'size' => 44])
                    <div class="min-w-0">
                        <a href="{{ route('profile.show', $application->user) }}"
                           class="fw-semibold text-decoration-none d-block">{{ $application->user->name }}</a>
                        <span class="small text-muted">
                            {{ $application->user->profile?->headline ?? $application->user->roleName() }}
                            &middot; applied {{ $application->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    @if ($application->resume_path)
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($application->resume_path) }}"
                           class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-file-earmark-text" aria-hidden="true"></i> Résumé
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
                            @foreach (\App\Models\JobApplication::STATUSES as $status)
                                <option value="{{ $status }}" @selected($application->status === $status)>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-info">Update</button>
                    </form>
                </div>
            </div>

            <div class="border-top mt-3 pt-3">
                <h2 class="h6 fw-bold small text-uppercase text-muted">Cover letter</h2>
                <div class="prose small mb-0">{{ $application->cover_letter }}</div>
            </div>
        </div>
    </article>
@empty
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @include('partials.empty', [
                'icon' => 'people',
                'title' => 'No applications yet',
                'message' => 'Applicants will appear here as soon as they apply.',
            ])
        </div>
    </div>
@endforelse

<nav aria-label="Applicant list pages" class="mt-4">
    {{ $applications->links() }}
</nav>
@endsection

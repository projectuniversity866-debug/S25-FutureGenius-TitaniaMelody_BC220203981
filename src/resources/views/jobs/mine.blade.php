@extends('layouts.app')
@section('title', 'Opportunities I posted')
@section('subtitle', 'Jobs & opportunities')

@section('content')
<div class="aq-page-head">
    <div>
        <h1>Opportunities I posted</h1>
        <p>Track applicants and keep listings current.</p>
    </div>
    <a href="{{ route('jobs.create') }}" class="btn btn-accent">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Post an opportunity
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <caption class="visually-hidden">Opportunities you have published</caption>
            <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Type</th>
                    <th scope="col">Status</th>
                    <th scope="col">Applicants</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr>
                        <th scope="row" class="fw-normal">
                            <a href="{{ route('jobs.show', $job) }}" class="fw-semibold text-decoration-none d-block">{{ $job->title }}</a>
                            <span style="font-size: .75rem; color: var(--aq-muted);">{{ $job->company }}</span>
                        </th>
                        <td><span class="badge {{ $job->type === 'internship' ? 'text-bg-warning' : 'text-bg-primary' }}">{{ ucfirst($job->type) }}</span></td>
                        <td>
                            @if ($job->isOpen())
                                <span class="badge text-bg-success">Open</span>
                            @elseif (! $job->is_active)
                                <span class="badge text-bg-secondary">Unpublished</span>
                            @else
                                <span class="badge text-bg-danger">Expired</span>
                            @endif
                        </td>
                        <td class="aq-num">{{ $job->applications_count }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('jobs.applicants', $job) }}" class="btn btn-sm btn-quiet">Applicants</a>
                                <a href="{{ route('jobs.edit', $job) }}" class="btn btn-sm btn-quiet">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            @include('partials.empty', [
                                'icon' => 'briefcase',
                                'title' => 'You have not posted anything yet',
                                'action' => ['url' => route('jobs.create'), 'label' => 'Post your first opportunity'],
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="Posting list pages" class="mt-4">
    {{ $jobs->links() }}
</nav>
@endsection

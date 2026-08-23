@extends('layouts.app')
@section('title', 'Opportunities I posted')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 fw-bold mb-0">Opportunities I posted</h1>
    <a href="{{ route('jobs.create') }}" class="btn btn-info fw-semibold">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Post an opportunity
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <caption class="visually-hidden">Opportunities you have published</caption>
            <thead class="table-light">
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
                        <th scope="row" class="fw-semibold">
                            <a href="{{ route('jobs.show', $job) }}" class="text-decoration-none">{{ $job->title }}</a>
                            <span class="d-block small text-muted fw-normal">{{ $job->company }}</span>
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
                        <td>{{ $job->applications_count }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('jobs.applicants', $job) }}" class="btn btn-sm btn-outline-dark">Applicants</a>
                                <a href="{{ route('jobs.edit', $job) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            @include('partials.empty', [
                                'icon' => 'briefcase',
                                'title' => 'You have not posted anything yet',
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

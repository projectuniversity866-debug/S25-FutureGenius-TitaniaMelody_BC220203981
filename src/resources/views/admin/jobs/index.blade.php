@extends('layouts.app')
@section('title', 'Moderate opportunities')

@section('content')
<h1 class="h4 fw-bold mb-1">Moderate opportunities</h1>
<p class="text-muted small mb-4">Unpublish an opportunity to hide it from the board, or delete it entirely.</p>

<form method="GET" action="{{ route('admin.jobs.index') }}" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-10">
                <label for="q" class="form-label small">Search</label>
                <input type="search" id="q" name="q" class="form-control form-control-sm"
                       value="{{ request('q') }}" placeholder="Title, company or location…">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-dark btn-sm">Search</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <caption class="visually-hidden">All posted opportunities</caption>
            <thead class="table-light">
                <tr>
                    <th scope="col">Opportunity</th>
                    <th scope="col">Posted by</th>
                    <th scope="col">Applicants</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr>
                        <th scope="row" class="fw-normal">
                            <a href="{{ route('jobs.show', $job) }}"
                               class="fw-semibold text-decoration-none d-block">{{ $job->title }}</a>
                            <span class="small text-muted">{{ $job->company }} &middot; {{ ucfirst($job->type) }}</span>
                        </th>
                        <td class="small">{{ $job->user->name }}</td>
                        <td>{{ $job->applications_count }}</td>
                        <td>
                            <span class="badge {{ $job->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $job->is_active ? 'Published' : 'Unpublished' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <form method="POST" action="{{ route('admin.jobs.toggle', $job) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        {{ $job->is_active ? 'Unpublish' : 'Publish' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}"
                                      onsubmit="return confirm('Delete this opportunity permanently?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">@include('partials.empty', ['icon' => 'briefcase', 'title' => 'No opportunities found'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="Opportunity list pages" class="mt-4">
    {{ $jobs->links() }}
</nav>
@endsection

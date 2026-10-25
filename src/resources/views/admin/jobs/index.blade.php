@extends('layouts.app')
@section('title', 'Moderate opportunities')
@section('subtitle', 'Administration')

@section('content')
<div class="aq-page-head">
    <div>
        <h1>Moderate opportunities</h1>
        <p>Unpublish an opportunity to hide it from the board, or delete it entirely.</p>
    </div>
</div>

<form method="GET" action="{{ route('admin.jobs.index') }}" class="aq-toolbar">
    <div class="row g-2 align-items-end">
        <div class="col-md-10">
            <label for="q" class="form-label">Search</label>
            <input type="search" id="q" name="q" class="form-control form-control-sm"
                   value="{{ request('q') }}" placeholder="Title, company or location…">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-accent btn-sm">Search</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <caption class="visually-hidden">All posted opportunities</caption>
            <thead>
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
                            <span style="font-size: .75rem; color: var(--aq-muted);">{{ $job->company }} · {{ ucfirst($job->type) }}</span>
                        </th>
                        <td>{{ $job->user->name }}</td>
                        <td class="aq-num">{{ $job->applications_count }}</td>
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
                                    <button type="submit" class="btn btn-sm btn-quiet">
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

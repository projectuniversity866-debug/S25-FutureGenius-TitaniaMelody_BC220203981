@extends('layouts.app')
@section('title', 'Moderate opportunities')
@section('subtitle', 'Administration')

@section('content')
<x-page-header title="Moderate opportunities"
               subtitle="Unpublish an opportunity to hide it from the board, or delete it entirely." />

<x-filter-panel :action="route('admin.jobs.index')" label="Search opportunities"
                :filters="['q' => 'Search']" :total="$jobs->total()" noun="opportunity">
    <div class="is-wide">
        <label for="q" class="form-label">Search</label>
        <input type="search" id="q" name="q" class="form-control form-control-sm"
               value="{{ request('q') }}" placeholder="Title, company or location…">
    </div>
</x-filter-panel>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle aq-table">
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
                            <div class="d-flex align-items-center gap-2">
                                <x-org-mark :name="$job->company" />
                                <div class="min-w-0">
                                    <a href="{{ route('jobs.show', $job) }}"
                                       class="fw-semibold text-decoration-none d-block">{{ $job->title }}</a>
                                    <span class="aq-text-xs aq-muted">{{ $job->company }} · {{ $job->typeLabel() }}</span>
                                </div>
                            </div>
                        </th>
                        <td data-label="Posted by"><span>{{ $job->user->name }}</span></td>
                        <td data-label="Applicants"><span class="aq-num">{{ $job->applications_count }}</span></td>
                        <td data-label="Status">
                            <span><x-status :value="$job->is_active ? 'published' : 'unpublished'" /></span>
                        </td>
                        <td data-label="Actions" class="aq-cell-actions">
                            <div class="aq-actions">
                                <form method="POST" action="{{ route('admin.jobs.toggle', $job) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-quiet">
                                        {{ $job->is_active ? 'Unpublish' : 'Publish' }}<span class="visually-hidden"> “{{ $job->title }}”</span>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}"
                                      data-confirm-title="Delete “{{ $job->title }}”?"
                                      data-confirm="The opportunity and all {{ $job->applications_count }} {{ \Illuminate\Support\Str::plural('application', $job->applications_count) }} to it are permanently deleted."
                                      data-confirm-button="Delete opportunity">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Delete<span class="visually-hidden"> “{{ $job->title }}”</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty icon="briefcase" title="No opportunities found" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="Opportunity list pages" class="mt-4">
    {{ $jobs->links() }}
</nav>
@endsection

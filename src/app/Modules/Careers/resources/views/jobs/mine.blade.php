@extends('layouts.app')
@section('title', 'Opportunities I posted')
@section('subtitle', 'Jobs & opportunities')

@section('content')
<x-page-header title="Opportunities I posted" subtitle="Track applicants and keep listings current.">
    <x-slot:actions>
        <a href="{{ route('jobs.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Post an opportunity
        </a>
    </x-slot:actions>
</x-page-header>

<x-card title="My organizations" icon="buildings" class="mb-4">
    <x-slot:actions>
        <a href="{{ route('organizations.create') }}" class="aq-text-sm">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add an organization
        </a>
    </x-slot:actions>
    @if ($organizations->isEmpty())
        <p class="aq-text-sm aq-muted mb-0">
            An organization profile gives your opportunities a home page with your logo mark, description and every open
            opening. <a href="{{ route('organizations.create') }}">Create one</a> and choose it when you post.
        </p>
    @else
        <ul class="aq-org-list">
            @foreach ($organizations as $organization)
                <li>
                    <x-org-mark :name="$organization->name" />
                    <a href="{{ route('organizations.show', $organization) }}" class="fw-semibold text-decoration-none">{{ $organization->name }}</a>
                    <span class="aq-text-xs aq-muted">{{ $organization->open_count }} open</span>
                    <a href="{{ route('organizations.edit', $organization) }}" class="btn btn-sm btn-quiet ms-auto">
                        Edit<span class="visually-hidden"> {{ $organization->name }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle aq-table">
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
                            <span class="aq-text-xs aq-muted">{{ $job->company }}</span>
                        </th>
                        <td data-label="Type">
                            <span><span class="badge {{ $job->typeBadge() }}">{{ $job->typeLabel() }}</span></span>
                        </td>
                        <td data-label="Status">
                            <span><x-status :value="$job->isOpen() ? 'open' : ($job->is_active ? 'expired' : 'unpublished')" /></span>
                        </td>
                        <td data-label="Applicants"><span class="aq-num">{{ $job->applications_count }}</span></td>
                        <td data-label="Actions" class="aq-cell-actions">
                            <div class="aq-actions">
                                <a href="{{ route('jobs.applicants', $job) }}" class="btn btn-sm btn-quiet">
                                    Applicants<span class="visually-hidden"> for “{{ $job->title }}”</span>
                                </a>
                                <a href="{{ route('jobs.edit', $job) }}" class="btn btn-sm btn-quiet">
                                    Edit<span class="visually-hidden"> “{{ $job->title }}”</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty icon="briefcase" title="You have not posted anything yet"
                                     :action="['url' => route('jobs.create'), 'label' => 'Post your first opportunity']" />
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

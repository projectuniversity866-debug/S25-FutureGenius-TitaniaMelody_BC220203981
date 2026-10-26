@extends('layouts.app')
@section('title', 'My applications')
@section('subtitle', 'Every opportunity you applied to')

@section('content')
<x-page-header title="My applications" subtitle="Status is updated by the person who posted the opportunity." />

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle aq-table">
            <caption class="visually-hidden">Opportunities you have applied to</caption>
            <thead>
                <tr>
                    <th scope="col">Opportunity</th>
                    <th scope="col">Company</th>
                    <th scope="col">Applied</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applications as $application)
                    @php $title = $application->jobPost?->title ?? 'Opportunity removed'; @endphp
                    <tr>
                        <th scope="row" class="fw-normal">
                            @if ($application->jobPost)
                                <a href="{{ route('jobs.show', $application->jobPost) }}"
                                   class="fw-semibold text-decoration-none">{{ $title }}</a>
                            @else
                                <span class="aq-muted">{{ $title }}</span>
                            @endif
                        </th>
                        <td data-label="Company"><span>{{ $application->jobPost?->company ?? '—' }}</span></td>
                        <td data-label="Applied">
                            <time class="aq-num aq-muted" datetime="{{ $application->created_at->toDateString() }}">
                                {{ $application->created_at->format('d M Y') }}
                            </time>
                        </td>
                        <td data-label="Status"><span><x-status :value="$application->status" /></span></td>
                        <td data-label="Action" class="aq-cell-actions">
                            <div class="aq-actions">
                                <a href="{{ route('applications.show', $application) }}" class="btn btn-sm btn-quiet">
                                    Progress<span class="visually-hidden"> of your application to “{{ $title }}”</span>
                                </a>
                                <form method="POST" action="{{ route('applications.destroy', $application) }}"
                                      data-confirm-title="Withdraw your application?"
                                      data-confirm="Your application to “{{ $title }}” and any uploaded CV are deleted. You can apply again while the opportunity is open."
                                      data-confirm-button="Withdraw application">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Withdraw<span class="visually-hidden"> application to “{{ $title }}”</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty icon="send" title="You have not applied to anything yet"
                                     message="Browse the jobs board to find an opportunity."
                                     :action="['url' => route('jobs.index'), 'label' => 'Browse opportunities']" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="Application list pages" class="mt-4">
    {{ $applications->links() }}
</nav>
@endsection

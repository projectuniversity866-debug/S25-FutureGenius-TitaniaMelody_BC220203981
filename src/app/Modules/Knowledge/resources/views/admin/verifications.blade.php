@extends('layouts.app')
@section('title', 'Expert verification requests')
@section('subtitle', 'Administration')

@section('content')
<x-page-header title="Expert verification"
               subtitle="Check each applicant's credentials and evidence. Approving a Member also makes them an Expert." />

<x-tabs label="Requests by status" :items="collect(\App\Modules\Knowledge\Models\ExpertVerification::STATUSES)->map(fn ($s) => [
    'label' => ucfirst($s), 'url' => route('admin.verifications.index', ['status' => $s]), 'active' => $status === $s, 'count' => $counts[$s] ?? 0,
])->all()" />

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle aq-table">
            <caption class="visually-hidden">{{ ucfirst($status) }} expert verification requests</caption>
            <thead>
                <tr>
                    <th scope="col">Applicant</th>
                    <th scope="col">Field</th>
                    <th scope="col">{{ $status === 'pending' ? 'Sent' : 'Decided' }}</th>
                    <th scope="col" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr>
                        <th scope="row" class="fw-normal">
                            <span class="d-flex align-items-center gap-2">
                                <x-avatar :user="$request->user" size="xs" />
                                <span>
                                    <span class="fw-semibold d-block">{{ $request->user->name }}</span>
                                    <span class="aq-text-xs aq-muted">{{ $request->user->roleName() }}</span>
                                </span>
                            </span>
                        </th>
                        <td data-label="Field"><span>{{ $request->field }}</span></td>
                        <td data-label="{{ $status === 'pending' ? 'Sent' : 'Decided' }}">
                            <span class="aq-num aq-muted">{{ ($status === 'pending' ? $request->created_at : $request->reviewed_at)?->format('d M Y') }}</span>
                        </td>
                        <td data-label="Action" class="aq-cell-actions">
                            <div class="aq-actions">
                                <a href="{{ route('admin.verifications.show', $request) }}" class="btn btn-sm {{ $status === 'pending' ? 'btn-accent' : 'btn-quiet' }}">
                                    {{ $status === 'pending' ? 'Review' : 'View' }}<span class="visually-hidden"> {{ $request->user->name }}’s request</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <x-empty icon="patch-check" title="No {{ $status }} requests" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="Request pages" class="mt-4">{{ $requests->links() }}</nav>
@endsection

@extends('layouts.app')
@section('title', 'Moderation queue')
@section('subtitle', 'Administration')

@section('content')
<x-page-header title="Moderation queue" subtitle="Reports from members, oldest first. Every decision goes into the moderation log.">
    <x-slot:actions>
        <a href="{{ route('admin.moderation.log') }}" class="btn btn-quiet btn-sm"><i class="bi bi-journal-check me-1" aria-hidden="true"></i> Moderation log</a>
    </x-slot:actions>
</x-page-header>

<x-tabs label="Reports by status" :items="collect(['open' => 'Open', 'actioned' => 'Removed', 'dismissed' => 'Dismissed'])->map(fn ($label, $s) => [
    'label' => $label, 'url' => route('admin.reports.index', ['status' => $s]), 'active' => $status === $s, 'count' => $counts[$s] ?? 0,
])->values()->all()" />

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle aq-table">
            <caption class="visually-hidden">{{ ucfirst($status) }} reports</caption>
            <thead>
                <tr>
                    <th scope="col">Content</th>
                    <th scope="col">Reason</th>
                    <th scope="col">Reported</th>
                    <th scope="col" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <th scope="row" class="fw-normal">
                            <span class="fw-semibold d-block">{{ $report->reportable?->reportLabel() ?? 'Content already removed' }}</span>
                            <span class="aq-text-xs aq-muted">by {{ $report->reporter?->name ?? 'a former member' }}</span>
                        </th>
                        <td data-label="Reason"><span>{{ $report->reasonLabel() }}</span></td>
                        <td data-label="Reported"><span class="aq-num aq-muted">{{ $report->created_at->diffForHumans() }}</span></td>
                        <td data-label="Action" class="aq-cell-actions">
                            <div class="aq-actions">
                                <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-sm {{ $status === 'open' ? 'btn-accent' : 'btn-quiet' }}">
                                    {{ $status === 'open' ? 'Review' : 'View' }}<span class="visually-hidden"> report #{{ $report->id }}</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty icon="shield-check" title="No {{ $status === 'actioned' ? 'removed' : $status }} reports" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<nav aria-label="Report pages" class="mt-4">{{ $reports->links() }}</nav>
@endsection

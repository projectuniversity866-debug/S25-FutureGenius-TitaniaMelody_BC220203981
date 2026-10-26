@extends('layouts.app')
@section('title', 'Moderation log')
@section('subtitle', 'Administration')

@section('content')
<x-page-header title="Moderation log"
               subtitle="Every moderation decision: what, who, why. Kept even after the content is gone. Administrative actions are in the Activity log."
               :breadcrumbs="['Moderation queue' => route('admin.reports.index'), 'Moderation log' => null]" />

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle aq-table">
            <caption class="visually-hidden">Moderation decisions, newest first</caption>
            <thead>
                <tr>
                    <th scope="col">Content</th>
                    <th scope="col">Decision</th>
                    <th scope="col">Reason</th>
                    <th scope="col">Moderator</th>
                    <th scope="col">When</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($actions as $action)
                    <tr>
                        <th scope="row" class="fw-normal">
                            <span class="fw-semibold d-block">{{ $action->subject_label }}</span>
                            @if ($action->note)<span class="aq-text-xs aq-muted">“{{ $action->note }}”</span>@endif
                        </th>
                        <td data-label="Decision"><span><x-status :value="$action->action === 'removed' ? 'rejected' : 'closed'" />
                            <span class="visually-hidden">{{ $action->action }}</span> <span class="aq-text-xs aq-muted">{{ $action->action === 'removed' ? 'Removed' : 'Kept' }} · {{ $action->reports_count }} {{ \Illuminate\Support\Str::plural('report', $action->reports_count) }}</span></span></td>
                        <td data-label="Reason"><span>{{ \App\Modules\Governance\Models\Report::REASONS[$action->reason] ?? '—' }}</span></td>
                        <td data-label="Moderator"><span>{{ $action->moderator?->name ?? 'a former moderator' }}</span></td>
                        <td data-label="When"><span class="aq-num aq-muted">{{ $action->created_at->format('d M Y H:i') }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty icon="journal-check" title="No decisions yet" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<nav aria-label="Log pages" class="mt-4">{{ $actions->links() }}</nav>
@endsection

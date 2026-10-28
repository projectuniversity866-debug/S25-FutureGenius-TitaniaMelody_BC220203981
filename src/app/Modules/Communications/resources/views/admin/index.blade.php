@extends('layouts.app')
@section('title', 'Newsletter')
@section('subtitle', 'Administration')

@section('content')
@php
    use App\Modules\Communications\Models\Campaign;
    $tabs = [['label' => 'All', 'url' => route('admin.newsletter.index'), 'active' => $status === null, 'count' => $counts->sum()]];
    foreach (Campaign::STATUSES as $value => [$label]) {
        $tabs[] = ['label' => $label, 'url' => route('admin.newsletter.index', ['status' => $value]), 'active' => $status === $value, 'count' => $counts[$value] ?? 0];
    }
@endphp
<x-page-header title="Newsletter" subtitle="Campaigns to everyone who confirmed their subscription.">
    <x-slot:actions>
        <a href="{{ route('admin.newsletter.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New campaign</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    <div class="col-sm-4"><x-stat label="Subscribed" :value="$subscribers['subscribed'] ?? 0" icon="envelope-check" /></div>
    <div class="col-sm-4"><x-stat label="Waiting for confirmation" :value="$subscribers['pending'] ?? 0" icon="hourglass-split" /></div>
    <div class="col-sm-4"><x-stat label="Unsubscribed" :value="$subscribers['unsubscribed'] ?? 0" icon="envelope-slash" /></div>
</div>

<x-tabs label="Campaign status" :items="$tabs" />

@if ($campaigns->isEmpty())
    <div class="card">
        <x-empty icon="envelope-paper" title="No campaigns here" :action="['url' => route('admin.newsletter.create'), 'label' => 'Write a campaign']" />
    </div>
@else
    <div class="card">
        <ul class="list-group list-group-flush">
            @foreach ($campaigns as $c)
                <li class="list-group-item d-flex flex-wrap align-items-center gap-3">
                    <div class="min-w-0 flex-grow-1">
                        <a href="{{ route('admin.newsletter.show', $c) }}" class="fw-semibold text-decoration-none">{{ $c->subject }}</a>
                        <span class="d-block aq-text-xs aq-muted">
                            @if ($c->status === 'scheduled') Goes out {{ $c->scheduleLabel() }}
                            @elseif ($c->status === 'draft') Updated {{ $c->updated_at->diffForHumans() }}
                            @else {{ $c->sent_count }} of {{ $c->recipients_count }} sent @if ($c->sent_at) · {{ $c->sent_at->format('d M Y') }} @endif
                            @endif
                        </span>
                    </div>
                    <span class="badge {{ ['draft' => 'text-bg-light', 'scheduled' => 'text-bg-primary', 'sending' => 'text-bg-warning', 'sent' => 'text-bg-success'][$c->status] }}">
                        <i class="bi bi-{{ Campaign::STATUSES[$c->status][1] }} me-1" aria-hidden="true"></i>{{ Campaign::STATUSES[$c->status][0] }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
    <nav aria-label="Campaign pages" class="mt-3">{{ $campaigns->links() }}</nav>
@endif
@endsection

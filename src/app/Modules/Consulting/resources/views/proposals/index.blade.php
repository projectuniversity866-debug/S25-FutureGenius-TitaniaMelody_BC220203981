@extends('layouts.app')
@section('title', 'Proposals')
@section('subtitle', 'Consulting')

@section('content')
@php
    use App\Modules\Consulting\Models\Proposal;
    $url = fn ($s) => route('consulting.proposals.index', array_filter(['side' => $side === 'received' && $profile ? 'received' : null, 'status' => $s]));
    $tabs = [['label' => 'All', 'url' => $url(null), 'active' => $status === null, 'count' => $counts->sum()]];
    foreach (Proposal::STATUSES as $value => [$label]) {
        if ($side === 'sent' || $value !== 'draft') {
            $tabs[] = ['label' => $label, 'url' => $url($value), 'active' => $status === $value, 'count' => $counts[$value] ?? 0];
        }
    }
@endphp
<x-page-header title="Proposals" subtitle="{{ $profile ? 'Offers you made to clients, and offers made to you.' : 'Offers consultants made to you.' }}">
    @if ($profile)
        <x-slot:actions>
            <a href="{{ route('consulting.clients.index') }}" class="btn btn-quiet"><i class="bi bi-person-lines-fill me-1" aria-hidden="true"></i> My clients</a>
        </x-slot:actions>
    @endif
</x-page-header>

@if ($profile)
    <x-tabs label="Whose proposals" :items="[
        ['label' => 'I sent', 'url' => route('consulting.proposals.index'), 'active' => $side === 'sent'],
        ['label' => 'Sent to me', 'url' => route('consulting.proposals.index', ['side' => 'received']), 'active' => $side === 'received'],
    ]" />
@endif
<x-tabs label="Proposal status" :items="$tabs" />

@if ($proposals->isEmpty())
    <div class="card">
        <x-empty icon="file-earmark-text" title="No proposals here"
                 :message="$side === 'sent' ? 'Write one from a client’s page under My clients.' : 'When a consultant sends you a proposal it appears here.'" />
    </div>
@else
    <div class="card">
        <ul class="list-group list-group-flush">
            @foreach ($proposals as $p)
                <li class="list-group-item d-flex flex-wrap align-items-center gap-3">
                    <div class="min-w-0 flex-grow-1">
                        <a href="{{ route('consulting.proposals.show', $p) }}" class="fw-semibold text-decoration-none">{{ $p->title }}</a>
                        <span class="d-block aq-text-xs aq-muted">
                            {{ $side === 'sent' ? 'For '.$p->client->name : 'From '.$p->profile->user->name }} · {{ $p->amountLabel() }} · updated {{ $p->updated_at->diffForHumans() }}
                        </span>
                    </div>
                    <span class="badge {{ ['draft' => 'text-bg-light', 'sent' => 'text-bg-primary', 'accepted' => 'text-bg-success', 'rejected' => 'text-bg-secondary', 'completed' => 'text-bg-success'][$p->status] }}">
                        <i class="bi bi-{{ Proposal::STATUSES[$p->status][1] }} me-1" aria-hidden="true"></i>{{ Proposal::STATUSES[$p->status][0] }}{{ $p->isExpired() ? ' · expired' : '' }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
    <nav aria-label="Proposal pages" class="mt-3">{{ $proposals->links() }}</nav>
@endif
@endsection

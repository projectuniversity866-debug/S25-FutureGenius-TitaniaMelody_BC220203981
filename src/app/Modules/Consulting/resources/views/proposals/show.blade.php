@extends('layouts.app')
@section('title', $proposal->title)
@section('subtitle', 'Proposal')

@section('content')
@php
    use App\Modules\Consulting\Models\Proposal;
    $p = $proposal;
    $isConsultant = auth()->id() === $p->profile->user_id;
    [$sLabel, $sIcon] = Proposal::STATUSES[$p->status];
@endphp
<x-page-header :title="$p->title" :subtitle="$p->amountLabel()"
               :breadcrumbs="$isConsultant
                   ? ['My clients' => route('consulting.clients.index'), $p->client->name => route('consulting.clients.show', $p->client), $p->title => null]
                   : ['Proposals' => route('consulting.proposals.index', ['side' => 'received']), $p->title => null]">
    @can('update', $p)
        <x-slot:actions>
            <a href="{{ route('consulting.proposals.edit', $p) }}" class="btn btn-quiet btn-sm"><i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit</a>
            <form method="POST" action="{{ route('consulting.proposals.destroy', $p) }}"
                  data-confirm-title="Delete this draft?" data-confirm="“{{ $p->title }}” is deleted. It was never sent." data-confirm-button="Delete draft">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Delete</button>
            </form>
        </x-slot:actions>
    @endcan
</x-page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <x-card class="mb-4">
            <x-markdown :text="$p->body" />
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card title="Details" icon="file-earmark-text" class="mb-4">
            <ul class="aq-event-facts">
                <li><i class="bi bi-{{ $sIcon }}" aria-hidden="true"></i><div><strong class="d-block">Status</strong>{{ $sLabel }}
                    @if ($p->isExpired()) · <span class="text-danger"><i class="bi bi-hourglass-bottom me-1" aria-hidden="true"></i>Expired</span>@endif</div></li>
                <li><i class="bi bi-person-badge" aria-hidden="true"></i><div><strong class="d-block">Consultant</strong>
                    <a href="{{ route('consultants.show', $p->profile) }}">{{ $p->profile->user->name }}</a></div></li>
                <li><i class="bi bi-person" aria-hidden="true"></i><div><strong class="d-block">Client</strong>{{ $p->client->name }}</div></li>
                <li><i class="bi bi-cash-coin" aria-hidden="true"></i><div><strong class="d-block">Price</strong>{{ $p->amountLabel() }}</div></li>
                @if ($p->valid_until)
                    <li><i class="bi bi-calendar-check" aria-hidden="true"></i><div><strong class="d-block">Valid until</strong>{{ $p->valid_until->format('D j M Y') }}</div></li>
                @endif
            </ul>
            <ol class="aq-text-xs aq-muted mb-0 mt-3 ps-3">
                <li>Drafted {{ $p->created_at->format('d M Y') }}</li>
                @if ($p->sent_at)<li>Sent {{ $p->sent_at->format('d M Y') }}</li>@endif
                @if ($p->decided_at)<li>{{ $p->status === 'rejected' ? 'Declined' : 'Accepted' }} {{ $p->decided_at->format('d M Y') }}</li>@endif
                @if ($p->completed_at)<li>Completed {{ $p->completed_at->format('d M Y') }}</li>@endif
            </ol>
            @if ($p->response_note)
                <h2 class="aq-label mt-3">The client's note</h2>
                <p class="aq-text-sm mb-0 aq-prewrap">{{ $p->response_note }}</p>
            @endif
        </x-card>

        @can('send', $p)
            <form method="POST" action="{{ route('consulting.proposals.send', $p) }}" class="mb-3"
                  data-confirm-title="Send this proposal?" data-confirm="{{ $p->client->user_id ? $p->client->name.' is told and can accept or decline it.' : $p->client->name.' is not on AQDesk: share it with them yourself and record their answer here.' }} You cannot edit it afterwards." data-confirm-button="Send">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-accent"><i class="bi bi-send me-1" aria-hidden="true"></i> Send to {{ $p->client->name }}</button>
            </form>
        @endcan

        @can('decide', $p)
            <x-card :title="$isConsultant ? 'Record the client\'s answer' : 'Your answer'" icon="chat-square-text" class="mb-3">
                <form method="POST" action="{{ route('consulting.proposals.decide', $p) }}" novalidate>
                    @csrf
                    @method('PUT')
                    <label for="note" class="form-label">Note <span class="aq-muted fw-normal">(optional)</span></label>
                    <textarea id="note" name="note" rows="2" maxlength="500" aria-describedby="note_hint @error('note') note_error @enderror"
                              class="form-control @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                    <div id="note_hint" class="form-text">{{ $isConsultant ? 'What the client said.' : 'Shown to '.$p->profile->user->name.'.' }}</div>
                    @error('note')<div id="note_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('decision')<div class="text-danger aq-text-sm mt-2" role="alert">{{ $message }}</div>@enderror
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="submit" name="decision" value="accepted" class="btn btn-accent"><i class="bi bi-hand-thumbs-up me-1" aria-hidden="true"></i> Accept</button>
                        <button type="submit" name="decision" value="rejected" class="btn btn-outline-danger"><i class="bi bi-hand-thumbs-down me-1" aria-hidden="true"></i> Decline</button>
                    </div>
                </form>
            </x-card>
        @endcan

        @can('complete', $p)
            <form method="POST" action="{{ route('consulting.proposals.complete', $p) }}">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-soft"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Mark the work as completed</button>
            </form>
        @endcan
    </div>
</div>
@endsection

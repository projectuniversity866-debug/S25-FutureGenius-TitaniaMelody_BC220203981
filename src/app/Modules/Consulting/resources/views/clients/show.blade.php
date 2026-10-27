@extends('layouts.app')
@section('title', $client->name.' · Client')
@section('subtitle', 'Consulting')

@section('content')
@php use App\Modules\Consulting\Models\Consultation; use App\Modules\Consulting\Models\ConsultingClient; use App\Modules\Consulting\Models\Proposal; [$sLabel, $sIcon] = ConsultingClient::STATUSES[$client->status]; @endphp
<x-page-header :title="$client->name" :subtitle="$client->organization" :breadcrumbs="['My clients' => route('consulting.clients.index'), $client->name => null]">
    <x-slot:actions>
        <a href="{{ route('consulting.clients.edit', $client) }}" class="btn btn-quiet btn-sm"><i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit</a>
        <form method="POST" action="{{ route('consulting.clients.destroy', $client) }}"
              data-confirm-title="Remove {{ $client->name }}?" data-confirm="The record and your notes are deleted. Sessions stay in your calendar." data-confirm-button="Remove client">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Remove</button>
        </form>
    </x-slot:actions>
</x-page-header>

<div class="row g-4">
    <div class="col-lg-5">
        <x-card title="Contact" icon="person-lines-fill" class="mb-4">
            <ul class="aq-event-facts">
                <li><i class="bi bi-{{ $sIcon }}" aria-hidden="true"></i><div><strong class="d-block">Status</strong>{{ $sLabel }}</div></li>
                <li><i class="bi bi-envelope" aria-hidden="true"></i><div><strong class="d-block">Email</strong>
                    @if ($client->email)<a href="mailto:{{ $client->email }}">{{ $client->email }}</a>@else — @endif</div></li>
                <li><i class="bi bi-telephone" aria-hidden="true"></i><div><strong class="d-block">Phone</strong>
                    @if ($client->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $client->phone) }}">{{ $client->phone }}</a>@else — @endif</div></li>
                <li><i class="bi bi-{{ $client->user_id ? 'person-check' : 'person-plus' }}" aria-hidden="true"></i><div><strong class="d-block">Source</strong>
                    {{ $client->source === 'booking' ? 'Booked a session on AQDesk' : 'Added by you' }}
                    @if ($client->user) · <a href="{{ route('profile.show', $client->user) }}">profile</a>@endif</div></li>
            </ul>
        </x-card>

        <x-card title="Proposals ({{ $client->proposals->count() }})" icon="file-earmark-text" class="mb-4" flush>
            <ul class="list-group list-group-flush">
                @foreach ($client->proposals as $p)
                    <li class="list-group-item">
                        <a href="{{ route('consulting.proposals.show', $p) }}" class="text-decoration-none">{{ $p->title }}</a>
                        <span class="d-block aq-text-xs aq-muted">
                            <i class="bi bi-{{ Proposal::STATUSES[$p->status][1] }} me-1" aria-hidden="true"></i>{{ Proposal::STATUSES[$p->status][0] }}{{ $p->isExpired() ? ' · expired' : '' }} · {{ $p->amountLabel() }}
                        </span>
                    </li>
                @endforeach
                <li class="list-group-item">
                    <a href="{{ route('consulting.proposals.create', $client) }}" class="btn btn-soft btn-sm"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New proposal</a>
                </li>
            </ul>
        </x-card>

        <x-card title="Sessions ({{ $client->consultations->count() }})" icon="calendar-week" flush>
            <ul class="list-group list-group-flush">
                @forelse ($client->consultations as $s)
                    <li class="list-group-item">
                        <a href="{{ route('consulting.sessions.show', $s) }}" class="text-decoration-none">{{ $s->topic }}</a>
                        <span class="d-block aq-text-xs aq-muted">{{ $s->whenLabel() }} · {{ Consultation::STATUSES[$s->status][0] }}</span>
                    </li>
                @empty
                    <li class="list-group-item aq-text-sm aq-muted">No sessions yet.</li>
                @endforelse
            </ul>
        </x-card>
    </div>

    <div class="col-lg-7">
        <x-card title="Private notes" icon="lock" id="notes" flush>
            <form method="POST" action="{{ route('consulting.clients.notes.store', $client) }}" class="card-body" novalidate>
                @csrf
                <label for="body" class="form-label">Add a note</label>
                <textarea id="body" name="body" rows="3" maxlength="5000" required aria-describedby="note_hint @error('body') note_error @enderror"
                          class="form-control @error('body') is-invalid @enderror">{{ old('body') }}</textarea>
                <div id="note_hint" class="form-text">Only you see these — never {{ $client->name }}.</div>
                @error('body')<div id="note_error" class="invalid-feedback">{{ $message }}</div>@enderror
                <button type="submit" class="btn btn-soft btn-sm mt-2">Save note</button>
            </form>
            <ol class="aq-reply-list">
                @foreach ($client->notes as $note)
                    <li>
                        <div class="d-flex align-items-center gap-2 mb-1 aq-text-xs aq-muted">
                            <time datetime="{{ $note->created_at->toIso8601String() }}" class="flex-grow-1">{{ $note->created_at->format('d M Y, H:i') }}</time>
                            <form method="POST" action="{{ route('consulting.clients.notes.destroy', [$client, $note]) }}"
                                  data-confirm-title="Delete this note?" data-confirm="It cannot be recovered." data-confirm-button="Delete note">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="aq-comment-delete">Delete<span class="visually-hidden"> note from {{ $note->created_at->format('d M Y') }}</span></button>
                            </form>
                        </div>
                        <p class="aq-text-sm mb-0 aq-prewrap">{{ $note->body }}</p>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>
</div>
@endsection

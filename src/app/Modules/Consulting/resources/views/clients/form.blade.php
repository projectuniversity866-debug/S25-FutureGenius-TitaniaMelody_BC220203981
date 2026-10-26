@extends('layouts.app')
@section('title', $client ? 'Edit client' : 'Add a client')
@section('subtitle', 'Consulting')

@section('content')
@php use App\Modules\Consulting\Models\ConsultingClient; $linked = (bool) $client?->user_id; @endphp
<x-page-header :title="$client ? 'Edit '.$client->name : 'Add a client'"
               :breadcrumbs="['My clients' => route('consulting.clients.index'), ($client ? $client->name : 'Add') => $client ? route('consulting.clients.show', $client) : null]" />

<div class="row">
    <div class="col-lg-7">
        <x-card>
            <form method="POST" action="{{ $client ? route('consulting.clients.update', $client) : route('consulting.clients.store') }}" novalidate>
                @csrf
                @if ($client) @method('PUT') @endif
                @if ($linked)
                    <p class="aq-text-sm aq-muted"><i class="bi bi-person-check me-1" aria-hidden="true"></i> {{ $client->name }} is an AQDesk member; their name and email come from their account.</p>
                @else
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $client?->name) }}" maxlength="255" required
                               class="form-control @error('name') is-invalid @enderror" @error('name') aria-describedby="name_error" @enderror>
                        @error('name')<div id="name_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="aq-muted fw-normal">(optional)</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email', $client?->email) }}" maxlength="255"
                               class="form-control @error('email') is-invalid @enderror" aria-describedby="email_hint @error('email') email_error @enderror">
                        <div id="email_hint" class="form-text">If it belongs to an AQDesk member, the record is linked to them and their bookings show here.</div>
                        @error('email')<div id="email_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="phone" class="form-label">Phone <span class="aq-muted fw-normal">(optional)</span></label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone', $client?->phone) }}" maxlength="40" autocomplete="off"
                               class="form-control @error('phone') is-invalid @enderror" @error('phone') aria-describedby="phone_error" @enderror>
                        @error('phone')<div id="phone_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label for="organization" class="form-label">Organization <span class="aq-muted fw-normal">(optional)</span></label>
                        <input type="text" id="organization" name="organization" value="{{ old('organization', $client?->organization) }}" maxlength="255" class="form-control">
                    </div>
                </div>
                <fieldset class="mb-3">
                    <legend class="form-label">Status</legend>
                    @foreach (ConsultingClient::STATUSES as $value => [$label, $icon, $hint])
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_{{ $value }}" value="{{ $value }}"
                                   @checked(old('status', $client?->status ?? 'lead') === $value)>
                            <label class="form-check-label" for="status_{{ $value }}"><i class="bi bi-{{ $icon }} me-1" aria-hidden="true"></i>{{ $label }} <span class="aq-muted aq-text-xs">— {{ $hint }}</span></label>
                        </div>
                    @endforeach
                </fieldset>
                <button type="submit" class="btn btn-accent">{{ $client ? 'Save client' : 'Add client' }}</button>
                <a href="{{ $client ? route('consulting.clients.show', $client) : route('consulting.clients.index') }}" class="btn btn-quiet">Cancel</a>
            </form>
        </x-card>
    </div>
</div>
@endsection

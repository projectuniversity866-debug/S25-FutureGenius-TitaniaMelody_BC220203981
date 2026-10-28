@extends('layouts.app')
@section('title', $campaign->subject)
@section('subtitle', 'Newsletter campaign')

@section('content')
@php use App\Modules\Communications\Models\Campaign; $c = $campaign; [$sLabel, $sIcon] = Campaign::STATUSES[$c->status]; $waiting = ($progress['pending'] ?? 0) + ($progress['sending'] ?? 0); @endphp
<x-page-header :title="$c->subject" :breadcrumbs="['Newsletter' => route('admin.newsletter.index'), $c->subject => null]">
    @can('update', $c)
        <x-slot:actions>
            <a href="{{ route('admin.newsletter.edit', $c) }}" class="btn btn-quiet btn-sm"><i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit</a>
            <form method="POST" action="{{ route('admin.newsletter.destroy', $c) }}"
                  data-confirm-title="Delete this draft?" data-confirm="“{{ $c->subject }}” is deleted. It was never sent." data-confirm-button="Delete draft">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Delete</button>
            </form>
        </x-slot:actions>
    @endcan
</x-page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <x-card title="Preview" icon="eye">
            <x-markdown :text="$c->body" />
            <p class="aq-text-xs aq-muted border-top pt-2 mt-3 mb-0">Each email ends with a one-click unsubscribe link.</p>
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card title="Status" icon="{{ $sIcon }}" class="mb-4">
            <p class="mb-2"><strong>{{ $sLabel }}</strong>
                @if ($c->status === 'scheduled') — goes out {{ $c->scheduleLabel() }}@endif
                @if ($c->sent_at) — finished {{ $c->sent_at->format('d M Y, H:i') }} UTC @endif
            </p>
            @if (in_array($c->status, ['sending', 'sent'], true))
                <ul class="aq-text-sm mb-0">
                    <li>{{ $progress['sent'] ?? 0 }} of {{ $c->recipients_count }} sent</li>
                    @if ($waiting)<li>{{ $waiting }} waiting in the queue</li>@endif
                    @if ($progress['failed'] ?? 0)<li>{{ $progress['failed'] }} failed</li>@endif
                    @if ($progress['skipped'] ?? 0)<li>{{ $progress['skipped'] }} skipped (unsubscribed meanwhile)</li>@endif
                </ul>
                <a href="{{ route('admin.newsletter.report', $c) }}" class="btn btn-soft btn-sm mt-3"><i class="bi bi-bar-chart me-1" aria-hidden="true"></i> Delivery report</a>
            @else
                <p class="aq-text-sm aq-muted mb-0">Will go to the {{ $audience }} confirmed {{ $audience === 1 ? 'subscriber' : 'subscribers' }} at the time of sending.</p>
            @endif
        </x-card>

        @if (in_array($c->status, ['draft', 'scheduled'], true))
            <form method="POST" action="{{ route('admin.newsletter.test', $c) }}" class="mb-3">
                @csrf
                <button type="submit" class="btn btn-soft"><i class="bi bi-envelope me-1" aria-hidden="true"></i> Send a test to {{ auth()->user()->email }}</button>
            </form>
        @endif

        @can('update', $c)
            <x-card title="Schedule" icon="clock" class="mb-3">
                <form method="POST" action="{{ route('admin.newsletter.schedule', $c) }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="row g-2 mb-2">
                        <div class="col-sm-6">
                            <label for="send_at" class="form-label">Send at</label>
                            <input type="datetime-local" id="send_at" name="send_at" value="{{ old('send_at') }}" required
                                   class="form-control @error('send_at') is-invalid @enderror" @error('send_at') aria-describedby="send_at_error" @enderror>
                            @error('send_at')<div id="send_at_error" class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="timezone" class="form-label">Time zone</label>
                            <select id="timezone" name="timezone" class="form-select">
                                @foreach (Campaign::TIMEZONES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('timezone', $c->timezone) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-soft btn-sm">Schedule</button>
                </form>
            </x-card>
        @endcan

        @can('unschedule', $c)
            <form method="POST" action="{{ route('admin.newsletter.unschedule', $c) }}" class="mb-3">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-quiet"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Cancel the schedule</button>
            </form>
        @endcan

        @can('send', $c)
            <form method="POST" action="{{ route('admin.newsletter.send', $c) }}"
                  data-confirm-title="Send to {{ $audience }} {{ $audience === 1 ? 'subscriber' : 'subscribers' }} now?" data-confirm="“{{ $c->subject }}” goes out from the queue straight away. This cannot be undone." data-confirm-button="Send now">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-accent"><i class="bi bi-send me-1" aria-hidden="true"></i> Send now</button>
            </form>
        @endcan
    </div>
</div>
@endsection

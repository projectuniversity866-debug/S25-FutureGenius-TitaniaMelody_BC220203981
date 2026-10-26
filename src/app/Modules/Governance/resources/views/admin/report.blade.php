@extends('layouts.app')
@section('title', 'Report #'.$report->id)
@section('subtitle', 'Moderation queue')

@section('content')
@php $item = $report->reportable; @endphp
<x-page-header :title="$item?->reportLabel() ?? 'Content already removed'" :subtitle="'Reported for: '.$report->reasonLabel()"
               :breadcrumbs="['Moderation queue' => route('admin.reports.index'), 'Report #'.$report->id => null]">
    <x-slot:actions>
        <x-status :value="['open' => 'pending', 'actioned' => 'rejected', 'dismissed' => 'closed'][$report->status]" />
    </x-slot:actions>
</x-page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <x-card title="The content" icon="file-earmark-text" class="mb-4">
            @if ($item)
                <p class="aq-text mb-2">{{ $item->reportExcerpt() }}</p>
                <p class="aq-text-xs aq-muted mb-0">
                    Written by {{ $item->reportAuthor()?->name ?? 'unknown' }} ·
                    <a href="{{ $item->reportUrl() }}" target="_blank" rel="noopener">Open it in context<span class="visually-hidden"> (opens in a new tab)</span></a>
                </p>
            @else
                <p class="aq-text-sm aq-muted mb-0">It has already been deleted (by its author or with other content). You can close the report.</p>
            @endif
        </x-card>

        <x-card title="Reports ({{ $related->count() + 1 }})" icon="flag">
            <ul class="aq-notes">
                @foreach ($related->prepend($report) as $r)
                    <li class="is-plain">
                        <strong class="aq-text-sm">{{ $r->reasonLabel() }}</strong>
                        <span class="aq-text-xs aq-muted"> · {{ $r->reporter?->name ?? 'a former member' }} · {{ $r->created_at->diffForHumans() }} · {{ $r->status }}</span>
                        @if ($r->details)<p class="aq-text-sm mb-0 mt-1">{{ $r->details }}</p>@endif
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>

    <div class="col-lg-5">
        @if ($report->status === 'open')
            <x-card title="Decision" icon="check2-square">
                <form method="POST" action="{{ route('admin.reports.decide', $report) }}">
                    @csrf
                    @method('PUT')
                    <fieldset class="mb-3">
                        <legend class="form-label">What should happen?</legend>
                        @if ($item)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="decision" id="decision_remove" value="remove" @checked(old('decision') === 'remove')>
                                <label class="form-check-label" for="decision_remove">Remove it — it breaks the guidelines (the author is told why)</label>
                            </div>
                        @endif
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="decision" id="decision_dismiss" value="dismiss" @checked(old('decision', $item ? null : 'dismiss') === 'dismiss')>
                            <label class="form-check-label" for="decision_dismiss">{{ $item ? 'Keep it — dismiss the report' : 'Close the report' }}</label>
                        </div>
                        @error('decision')<div class="aq-text-sm aq-danger mt-1" role="alert">{{ $message }}</div>@enderror
                    </fieldset>
                    <div class="mb-3">
                        <label for="note" class="form-label">Note <span class="aq-muted fw-normal">(optional — sent to the author when removing)</span></label>
                        <textarea id="note" name="note" rows="3" maxlength="1000" class="form-control">{{ old('note') }}</textarea>
                    </div>
                    <p class="aq-text-xs aq-muted">The decision covers every open report on this content.</p>
                    <button type="submit" class="btn btn-accent btn-sm">Save decision</button>
                </form>
            </x-card>
        @else
            <x-card title="Decision" icon="check2-square">
                <p class="aq-text-sm mb-1">{{ $report->status === 'actioned' ? 'Removed' : 'Dismissed' }} by {{ $report->moderator?->name ?? 'a former moderator' }} {{ $report->decided_at?->diffForHumans() }}.</p>
                @if ($report->decision_note)<blockquote class="aq-timeline-message mb-0">{{ $report->decision_note }}</blockquote>@endif
            </x-card>
        @endif
    </div>
</div>
@endsection

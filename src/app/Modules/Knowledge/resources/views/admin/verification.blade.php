@extends('layouts.app')
@section('title', 'Review: '.$verification->user->name)
@section('subtitle', 'Expert verification')

@section('content')
<x-page-header :title="$verification->user->name" :subtitle="'Asks to be verified in “'.$verification->field.'”'"
               :breadcrumbs="['Expert verification' => route('admin.verifications.index'), $verification->user->name => null]">
    <x-slot:actions>
        <x-status :value="$verification->status === 'approved' ? 'accepted' : ($verification->status === 'rejected' ? 'rejected' : 'pending')" />
    </x-slot:actions>
</x-page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <x-card title="Credentials" icon="mortarboard" class="mb-4">
            <div class="prose aq-text">{{ $verification->credentials }}</div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                @if ($verification->evidence_url)
                    <a href="{{ $verification->evidence_url }}" target="_blank" rel="noopener noreferrer nofollow" class="btn btn-sm btn-quiet">
                        <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> Evidence link<span class="visually-hidden"> (opens in a new tab)</span>
                    </a>
                @endif
                @if ($verification->document_path)
                    <a href="{{ route('verification.document', $verification) }}" class="btn btn-sm btn-quiet">
                        <i class="bi bi-download me-1" aria-hidden="true"></i> Supporting document
                    </a>
                @endif
            </div>
        </x-card>

        <x-card title="Applicant" icon="person">
            <div class="d-flex align-items-center gap-3 mb-2">
                <x-avatar :user="$verification->user" size="md" />
                <div>
                    <a href="{{ route('profile.show', $verification->user) }}" class="fw-semibold">{{ $verification->user->name }}</a>
                    <span class="d-block aq-text-xs aq-muted">{{ $verification->user->roleName() }} · member since {{ $verification->user->created_at->format('M Y') }}</span>
                </div>
            </div>
            @if ($verification->user->profile?->headline)
                <p class="aq-text-sm mb-1">{{ $verification->user->profile->headline }}</p>
            @endif
            @if ($history->isNotEmpty())
                <p class="aq-text-xs aq-muted mb-0">Earlier requests: {{ $history->map(fn ($h) => $h->field.' ('.$h->status.')')->implode(', ') }}</p>
            @endif
        </x-card>
    </div>

    <div class="col-lg-5">
        @if ($verification->isPending())
            <x-card title="Decision" icon="check2-square">
                <form method="POST" action="{{ route('admin.verifications.decide', $verification) }}">
                    @csrf
                    @method('PUT')
                    <fieldset class="mb-3">
                        <legend class="form-label">Decision</legend>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="decision" id="decision_approve" value="approve" @checked(old('decision', 'approve') === 'approve')>
                            <label class="form-check-label" for="decision_approve">Approve — show the verified badge{{ $verification->user->isMember() ? ' and make them an Expert' : '' }}</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="decision" id="decision_reject" value="reject" @checked(old('decision') === 'reject')>
                            <label class="form-check-label" for="decision_reject">Reject</label>
                        </div>
                    </fieldset>
                    <div class="mb-3">
                        <label for="review_note" class="form-label">Note to the applicant <span class="aq-muted fw-normal">(required when rejecting)</span></label>
                        <textarea id="review_note" name="review_note" rows="4" maxlength="1000"
                                  class="form-control @error('review_note') is-invalid @enderror">{{ old('review_note') }}</textarea>
                        @error('review_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-accent btn-sm">Save decision</button>
                </form>
            </x-card>
        @else
            <x-card title="Decision" icon="check2-square">
                <p class="aq-text-sm mb-1">
                    {{ ucfirst($verification->status) }} by {{ $verification->reviewer?->name ?? 'a former administrator' }}
                    on {{ $verification->reviewed_at?->format('d M Y') }}.
                </p>
                @if ($verification->review_note)
                    <blockquote class="aq-timeline-message mb-0">{{ $verification->review_note }}</blockquote>
                @endif
            </x-card>
        @endif
    </div>
</div>
@endsection

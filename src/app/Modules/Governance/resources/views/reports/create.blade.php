@extends('layouts.app')
@section('title', 'Report content')
@section('subtitle', 'Community guidelines')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Report content"
               subtitle="Tell the moderators what is wrong. Your report is private: the author is not told who reported it." />

<x-card title="You are reporting" icon="flag" class="mb-4">
    <p class="fw-semibold mb-1"><a href="{{ $item->reportUrl() }}">{{ $item->reportLabel() }}</a></p>
    <p class="aq-text-sm aq-muted mb-0">{{ \Illuminate\Support\Str::limit($item->reportExcerpt(), 300) }}</p>
</x-card>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('reports.store', [$type, $id]) }}" novalidate>
            @csrf
            <fieldset class="mb-3" @error('reason') aria-describedby="reason_error" @enderror>
                <legend class="form-label">What is wrong with it?</legend>
                @foreach (\App\Modules\Governance\Models\Report::REASONS as $value => $label)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="reason" id="reason_{{ $value }}" value="{{ $value }}" @checked(old('reason') === $value)>
                        <label class="form-check-label" for="reason_{{ $value }}">{{ $label }}</label>
                    </div>
                @endforeach
                @error('reason')<div id="reason_error" class="aq-text-sm aq-danger mt-1">{{ $message }}</div>@enderror
            </fieldset>
            <div class="mb-3">
                <label for="details" class="form-label">Details <span class="aq-muted fw-normal">(required for “Something else”)</span></label>
                <textarea id="details" name="details" rows="4" maxlength="1000"
                          class="form-control @error('details') is-invalid @enderror">{{ old('details') }}</textarea>
                @error('details')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex flex-wrap align-items-center gap-3">
                <button type="submit" class="btn btn-accent">Send report</button>
                <a href="{{ $item->reportUrl() }}" class="btn btn-quiet">Cancel</a>
                <a href="{{ route('guidelines') }}" class="aq-text-sm ms-auto">Read the community guidelines</a>
            </div>
        </form>
    </div>
</div>
@endsection

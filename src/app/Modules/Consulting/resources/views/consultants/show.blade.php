@extends('layouts.app')
@section('title', $profile->user->name.' · Consultant')
@section('subtitle', 'Consulting')

@section('content')
@php use App\Modules\Consulting\Models\ConsultantProfile; @endphp
<x-page-header :title="$profile->user->name" :subtitle="$profile->headline" :breadcrumbs="['Consultants' => route('consultants.index'), $profile->user->name => null]">
    @if (auth()->id() === $profile->user_id)
        <x-slot:actions>
            <a href="{{ route('consulting.profile') }}" class="btn btn-quiet btn-sm"><i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit profile</a>
            <a href="{{ route('consulting.availability') }}" class="btn btn-quiet btn-sm"><i class="bi bi-calendar-week me-1" aria-hidden="true"></i> Availability</a>
        </x-slot:actions>
    @endif
</x-page-header>

@unless ($profile->is_accepting)
    <div class="alert alert-secondary py-2 aq-text-sm" role="status"><i class="bi bi-pause-circle me-1" aria-hidden="true"></i> Not taking bookings right now — only you can see this page.</div>
@endunless

<div class="row g-4">
    <div class="col-lg-7">
        <x-card class="mb-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <x-avatar :user="$profile->user" size="lg" />
                <div>
                    <a href="{{ route('profile.show', $profile->user) }}" class="fw-semibold">{{ $profile->user->name }}</a>
                    <span class="d-block aq-text-sm aq-muted">{{ $profile->session_minutes }}-minute sessions · {{ $profile->priceLabel() }}</span>
                    <span class="d-block aq-text-xs aq-muted">Times shown in {{ ConsultantProfile::TIMEZONES[$profile->timezone] ?? $profile->timezone }}</span>
                </div>
            </div>
            @if ($profile->expertise)
                <h2 class="aq-label">Can help with</h2>
                <ul class="list-unstyled d-flex flex-wrap gap-1 mb-3">
                    @foreach ($profile->expertise as $topic)<li class="badge text-bg-light">{{ $topic }}</li>@endforeach
                </ul>
            @endif
            @if ($profile->bio)
                <x-markdown :text="$profile->bio" />
            @endif
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card title="Available times" icon="calendar-week" id="times">
            <p class="aq-text-xs aq-muted">The next 14 days, at least {{ $profile->notice_hours }} {{ \Illuminate\Support\Str::plural('hour', $profile->notice_hours) }} from now. Online booking opens in the next release.</p>
            @forelse ($slots as $date => $times)
                @php $day = \Carbon\CarbonImmutable::parse($date, $profile->timezone); @endphp
                <h3 class="aq-label mt-3 mb-1">{{ $day->format('l j F') }}</h3>
                <ul class="aq-slot-list" aria-label="Free times on {{ $day->format('l j F') }}">
                    @foreach ($times as $time)
                        <li><time datetime="{{ $time->toIso8601String() }}">{{ $time->format('H:i') }}</time></li>
                    @endforeach
                </ul>
            @empty
                <p class="aq-text-sm aq-muted mb-0"><i class="bi bi-calendar-x me-1" aria-hidden="true"></i> No free times in the next two weeks.</p>
            @endforelse
        </x-card>
    </div>
</div>
@endsection

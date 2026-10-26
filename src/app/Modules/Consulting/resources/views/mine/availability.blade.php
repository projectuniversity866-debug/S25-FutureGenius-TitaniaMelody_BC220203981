@extends('layouts.app')
@section('title', 'My availability')
@section('subtitle', 'Consulting')

@section('content')
@php use App\Modules\Consulting\Models\ConsultantProfile; $byDay = $profile->rules->groupBy('weekday'); @endphp
<x-page-header title="My availability" :subtitle="'Weekly hours in '.(ConsultantProfile::TIMEZONES[$profile->timezone] ?? $profile->timezone).', cut into '.$profile->session_minutes.'-minute sessions.'"
               :breadcrumbs="['Consultants' => route('consultants.index'), 'My availability' => null]">
    <x-slot:actions>
        <a href="{{ route('consultants.show', $profile) }}" class="btn btn-quiet btn-sm"><i class="bi bi-eye me-1" aria-hidden="true"></i> View public page</a>
        <a href="{{ route('consulting.profile') }}" class="btn btn-quiet btn-sm"><i class="bi bi-person-gear me-1" aria-hidden="true"></i> Profile & settings</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <x-card title="Weekly hours" icon="calendar-week" class="mb-4" flush>
            <ul class="list-group list-group-flush">
                @foreach (ConsultantProfile::WEEKDAYS as $n => $dayName)
                    <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
                        <strong class="aq-weekday">{{ $dayName }}</strong>
                        @forelse ($byDay->get($n, []) as $rule)
                            <form method="POST" action="{{ route('consulting.hours.destroy', $rule) }}" class="d-inline-flex">
                                @csrf
                                @method('DELETE')
                                <span class="badge text-bg-light d-inline-flex align-items-center gap-1">
                                    {{ $rule->starts }}–{{ $rule->ends }}
                                    <button type="submit" class="btn btn-link btn-sm p-0 aq-danger">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Remove {{ $rule->label() }}</span>
                                    </button>
                                </span>
                            </form>
                        @empty
                            <span class="aq-text-sm aq-muted">Unavailable</span>
                        @endforelse
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('consulting.hours.store') }}" class="card-body aq-border-top" novalidate>
                @csrf
                <h3 class="aq-label mb-2">Add hours</h3>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-4">
                        <label for="weekday" class="form-label">Day</label>
                        <select id="weekday" name="weekday" class="form-select form-select-sm">
                            @foreach (ConsultantProfile::WEEKDAYS as $n => $dayName)
                                <option value="{{ $n }}" @selected((int) old('weekday', 1) === $n)>{{ $dayName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label for="starts" class="form-label">From</label>
                        <input type="time" id="starts" name="starts" value="{{ old('starts', '09:00') }}" step="900"
                               class="form-control form-control-sm @error('starts') is-invalid @enderror" @error('starts') aria-describedby="starts_error" @enderror>
                    </div>
                    <div class="col-sm-3">
                        <label for="ends" class="form-label">To</label>
                        <input type="time" id="ends" name="ends" value="{{ old('ends', '12:00') }}" step="900"
                               class="form-control form-control-sm @error('ends') is-invalid @enderror" @error('ends') aria-describedby="ends_error" @enderror>
                    </div>
                    <div class="col-sm-2"><button type="submit" class="btn btn-soft btn-sm w-100">Add</button></div>
                </div>
                @error('starts')<div id="starts_error" class="aq-text-sm aq-danger mt-1">{{ $message }}</div>@enderror
                @error('ends')<div id="ends_error" class="aq-text-sm aq-danger mt-1">{{ $message }}</div>@enderror
                <p class="aq-text-xs aq-muted mt-2 mb-0">Add several blocks for one day (e.g. 09:00–12:00 and 14:00–17:00) to keep a lunch break.</p>
            </form>
        </x-card>

        <x-card title="Days off" icon="calendar-x" flush>
            <ul class="list-group list-group-flush">
                @forelse ($upcomingDaysOff as $day)
                    <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
                        <span class="flex-grow-1">{{ $day->date->format('l j F Y') }}@if ($day->reason) <span class="aq-text-xs aq-muted">· {{ $day->reason }}</span>@endif</span>
                        <form method="POST" action="{{ route('consulting.days-off.destroy', $day) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-quiet">Remove<span class="visually-hidden"> day off on {{ $day->date->format('j F Y') }}</span></button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item aq-text-sm aq-muted">No days off planned.</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('consulting.days-off.store') }}" class="card-body aq-border-top" novalidate>
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-sm-4">
                        <label for="date" class="form-label">Day off</label>
                        <input type="date" id="date" name="date" value="{{ old('date') }}" min="{{ now($profile->timezone)->toDateString() }}"
                               class="form-control form-control-sm @error('date') is-invalid @enderror" @error('date') aria-describedby="date_error" @enderror>
                    </div>
                    <div class="col-sm-6">
                        <label for="reason" class="form-label">Note <span class="aq-muted fw-normal">(only you see it)</span></label>
                        <input type="text" id="reason" name="reason" maxlength="150" value="{{ old('reason') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-2"><button type="submit" class="btn btn-soft btn-sm w-100">Add</button></div>
                </div>
                @error('date')<div id="date_error" class="aq-text-sm aq-danger mt-1">{{ $message }}</div>@enderror
            </form>
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card title="What members see this week" icon="eye">
            @unless ($profile->is_accepting)
                <p class="aq-text-sm aq-danger"><i class="bi bi-pause-circle me-1" aria-hidden="true"></i> You are not taking bookings, so this is hidden.</p>
            @endunless
            @forelse ($preview as $date => $times)
                <h3 class="aq-label mt-2 mb-1">{{ \Carbon\CarbonImmutable::parse($date, $profile->timezone)->format('l j F') }}</h3>
                <ul class="aq-slot-list">
                    @foreach ($times as $time)<li>{{ $time->format('H:i') }}</li>@endforeach
                </ul>
            @empty
                <p class="aq-text-sm aq-muted mb-0">No free times in the next 7 days. Add weekly hours, or check your notice period ({{ $profile->notice_hours }} h).</p>
            @endforelse
        </x-card>
    </div>
</div>
@endsection

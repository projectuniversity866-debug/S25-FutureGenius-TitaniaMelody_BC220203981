@extends('layouts.app')
@section('title', 'Job alerts')
@section('subtitle', 'Jobs & opportunities')

@section('content')
<x-page-header title="Job alerts"
               subtitle="You are notified as soon as a matching opportunity is published — under the bell, and by email unless you turn it off in Account settings."
               :breadcrumbs="['Jobs & opportunities' => route('jobs.index'), 'Job alerts' => null]" />

<div class="row g-4">
    <div class="col-lg-7">
        <x-card title="Your alerts ({{ $alerts->count() }} of {{ \App\Modules\Careers\Models\JobAlert::MAX_PER_USER }})" icon="bell" flush>
            <ul class="list-group list-group-flush">
                @forelse ($alerts as $alert)
                    <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
                        <div class="min-w-0 flex-grow-1">
                            <a href="{{ $alert->boardUrl() }}" class="fw-semibold text-decoration-none">{{ $alert->describe() }}</a>
                            <span class="d-block aq-text-xs aq-muted">
                                Created {{ $alert->created_at->format('d M Y') }}
                                · {{ $alert->last_sent_at ? 'last match '.$alert->last_sent_at->diffForHumans() : 'no matches yet' }}
                            </span>
                        </div>
                        <form method="POST" action="{{ route('alerts.destroy', $alert) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                Delete<span class="visually-hidden"> alert {{ $alert->describe() }}</span>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item">
                        <x-empty icon="bell" title="No job alerts yet"
                                 message="Create one here, or filter the board and choose “Create job alert”." />
                    </li>
                @endforelse
            </ul>
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card title="New alert" icon="plus-circle">
            <form method="POST" action="{{ route('alerts.store') }}">
                @csrf
                @error('alert')<div class="alert alert-danger py-2 aq-text-sm" role="alert">{{ $message }}</div>@enderror
                <div class="mb-3">
                    <label for="keywords" class="form-label">Keywords <span class="aq-muted fw-normal">(optional)</span></label>
                    <input type="text" id="keywords" name="keywords" maxlength="120" value="{{ old('keywords') }}"
                           class="form-control @error('keywords') is-invalid @enderror" placeholder="e.g. Laravel, Lahore" aria-describedby="keywords_hint">
                    <div id="keywords_hint" class="form-text">Matched against the title, organization and location.</div>
                    @error('keywords')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="alert_type" class="form-label">Type</label>
                    <select id="alert_type" name="type" class="form-select">
                        <option value="">Any type</option>
                        @foreach (\App\Modules\Careers\Models\JobPost::TYPES as $key => $info)
                            <option value="{{ $key }}" @selected(old('type') === $key)>{{ $info[1] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="work_mode" class="form-label">Work mode</label>
                    <select id="work_mode" name="work_mode" class="form-select">
                        <option value="">Any</option>
                        <option value="onsite" @selected(old('work_mode') === 'onsite')>On-site</option>
                        <option value="remote" @selected(old('work_mode') === 'remote')>Remote</option>
                        <option value="hybrid" @selected(old('work_mode') === 'hybrid')>Hybrid</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-accent btn-sm">Create alert</button>
            </form>
        </x-card>
    </div>
</div>
@endsection

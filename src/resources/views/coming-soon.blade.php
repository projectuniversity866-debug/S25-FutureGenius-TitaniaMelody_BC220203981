@extends('layouts.app')
@section('title', $module['title'])
@section('subtitle', 'Coming soon')
@section('container', 'aq-container-narrow')

@section('content')
<div class="card module-placeholder">
    <div class="card-body text-center p-4">
        <span class="aq-module-icon" aria-hidden="true"><i class="bi bi-{{ $module['icon'] }}"></i></span>

        <span class="badge text-bg-warning">Coming soon</span>

        <h1 class="aq-h1 mt-3">{{ $module['title'] }}</h1>
        <p class="aq-muted aq-measure mx-auto mb-0">{{ $module['summary'] }}</p>

        <div class="text-start mt-4 mx-auto aq-placeholder-list">
            <h2 class="aq-label mb-2">Planned features</h2>
            <ul class="list-unstyled d-grid gap-1 mb-0">
                @foreach ($module['features'] as $feature)
                    <li class="d-flex align-items-center gap-2 py-1 aq-text aq-ink-2">
                        <i class="bi bi-check2-circle aq-accent" aria-hidden="true"></i>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="mt-4 mb-0 aq-text-xs aq-muted">
            Requirement source: <strong>{{ $module['source'] }}</strong>.
        </p>

        <a href="{{ url()->previous() }}" class="btn btn-quiet btn-sm mt-4">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Go back
        </a>
    </div>
</div>
@endsection

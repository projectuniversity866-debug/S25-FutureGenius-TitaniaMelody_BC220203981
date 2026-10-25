@extends('layouts.app')
@section('title', $module['title'])
@section('subtitle', 'Planned for Phase 2')
@section('container', 'aq-container-narrow')

@section('content')
<div class="card module-placeholder">
    <div class="card-body text-center p-4">
        <span class="aq-module-icon" aria-hidden="true"><i class="bi bi-{{ $module['icon'] }}"></i></span>

        <span class="badge text-bg-warning">Planned for Phase 2</span>

        <h1 style="font-size: 1.5rem; font-weight: 700; margin: .875rem 0 .375rem;">{{ $module['title'] }}</h1>
        <p style="font-size: .9375rem; color: var(--aq-muted); max-width: 46ch; margin: 0 auto;">{{ $module['summary'] }}</p>

        <div class="text-start mt-4 mx-auto" style="max-width: 420px;">
            <h2 class="aq-label mb-2">Planned features</h2>
            <ul class="list-unstyled d-grid gap-1 mb-0">
                @foreach ($module['features'] as $feature)
                    <li class="d-flex align-items-center gap-2 py-1" style="font-size: .875rem; color: var(--aq-ink-2);">
                        <i class="bi bi-dot" style="color: var(--aq-accent);" aria-hidden="true"></i>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="mt-4 mb-0" style="font-size: .75rem; color: var(--aq-muted);">
            Requirement source: <strong>{{ $module['source'] }}</strong>. Part of the final
            project scope, intentionally not implemented in the prototype.
        </p>

        <a href="{{ url()->previous() }}" class="btn btn-quiet btn-sm mt-4">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Go back
        </a>
    </div>
</div>
@endsection

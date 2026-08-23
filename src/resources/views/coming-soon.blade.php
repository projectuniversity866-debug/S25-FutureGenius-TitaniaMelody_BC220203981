@extends('layouts.app')
@section('title', $module['title'])

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm module-placeholder">
            <div class="card-body text-center p-5">
                <i class="bi bi-{{ $module['icon'] }} text-info d-block mb-3" aria-hidden="true"></i>

                <span class="badge text-bg-warning mb-3">Planned for Phase 2</span>

                <h1 class="h3 fw-bold">{{ $module['title'] }}</h1>
                <p class="text-muted">{{ $module['summary'] }}</p>

                <div class="row justify-content-center mt-4">
                    <div class="col-md-8 text-start">
                        <h2 class="h6 fw-bold">Planned features</h2>
                        <ul class="list-group list-group-flush">
                            @foreach ($module['features'] as $feature)
                                <li class="list-group-item d-flex align-items-center gap-2 px-0">
                                    <i class="bi bi-circle text-muted small" aria-hidden="true"></i>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <p class="small text-muted mt-4 mb-0">
                    Requirement source: <strong>{{ $module['source'] }}</strong>.
                    This module is part of the final project scope and is intentionally
                    not implemented in the prototype.
                </p>

                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm mt-4">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Go back
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

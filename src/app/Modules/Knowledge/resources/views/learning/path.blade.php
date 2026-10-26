@extends('layouts.app')
@section('title', $path->title)
@section('subtitle', 'Learning path')

@section('content')
@php
    $total = $path->steps->count();
    $doneCount = count($done);
    $pct = $total ? (int) round($doneCount / $total * 100) : 0;
@endphp

<x-page-header :title="$path->title" :subtitle="$path->summary"
               :breadcrumbs="['Learning Hub' => route('learning.index'), $path->title => null]">
    @can('update', $path)
        <x-slot:actions>
            <a href="{{ route('learning.paths.edit', $path) }}" class="btn btn-quiet btn-sm">
                <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit path
            </a>
        </x-slot:actions>
    @endcan
</x-page-header>

@unless ($path->isPublished())
    <div class="alert alert-warning py-2 aq-text-sm" role="status">Draft — only the curator and moderators can see it.</div>
@endunless

<div class="row g-4">
    <div class="col-lg-8">
        <section aria-labelledby="steps-heading">
            <div class="aq-section-head"><h2 id="steps-heading">{{ $total }} {{ \Illuminate\Support\Str::plural('step', $total) }}</h2></div>
            <ol class="aq-path-steps">
                @foreach ($path->steps as $i => $step)
                    @php $isDone = in_array($step->id, $done, true); @endphp
                    <li id="step-{{ $step->id }}" @class(['card', 'is-done' => $isDone])>
                        <div class="card-body d-flex gap-3">
                            <span class="aq-pipeline-dot flex-shrink-0" aria-hidden="true">
                                @if ($isDone)<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif
                            </span>
                            <ul class="aq-resource-list flex-grow-1 min-w-0">
                                @include('knowledge::learning._resource', ['resource' => $step->resource, 'note' => $step->note])
                            </ul>
                            @auth
                                <form method="POST" action="{{ route('learning.steps.toggle', $step) }}" class="flex-shrink-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $isDone ? 'btn-soft' : 'btn-quiet' }}" aria-pressed="{{ $isDone ? 'true' : 'false' }}"
                                            aria-label="Step {{ $i + 1 }} done: {{ $step->resource->title }}">
                                        <i class="bi bi-{{ $isDone ? 'check-circle-fill' : 'circle' }} me-1" aria-hidden="true"></i>Done
                                    </button>
                                </form>
                            @endauth
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    <div class="col-lg-4">
        <aside class="card mb-3">
            <div class="card-body">
                <h2 class="aq-label mb-3">Your progress</h2>
                @auth
                    <progress class="aq-progress mb-2" max="{{ max(1, $total) }}" value="{{ $doneCount }}" aria-label="Steps done">{{ $pct }}%</progress>
                    <p class="aq-text-sm mb-0"><strong>{{ $doneCount }} of {{ $total }}</strong> steps done ({{ $pct }}%)</p>
                    @if ($total && $doneCount === $total)
                        <p class="aq-text-sm aq-accent-ink mt-2 mb-0"><i class="bi bi-trophy me-1" aria-hidden="true"></i> Path complete — well done!</p>
                    @endif
                @else
                    <p class="aq-text-sm aq-muted mb-0"><a href="{{ route('login') }}">Sign in</a> to tick off steps and keep your place.</p>
                @endauth
            </div>
        </aside>
        <aside class="card">
            <div class="card-body">
                <h2 class="aq-label mb-3">About this path</h2>
                <dl class="aq-dl mb-0">
                    <dt>Level</dt><dd>{{ \App\Modules\Knowledge\Models\LearningResource::LEVELS[$path->level] ?? $path->level }}</dd>
                    @if ($path->category)<dt>Topic</dt><dd>{{ $path->category->name }}</dd>@endif
                    @if ($path->totalMinutes())<dt>Estimated time</dt><dd>{{ intdiv($path->totalMinutes(), 60) ? intdiv($path->totalMinutes(), 60).' h ' : '' }}{{ $path->totalMinutes() % 60 ? ($path->totalMinutes() % 60).' min' : '' }}</dd>@endif
                    <dt>Curated by</dt><dd><a href="{{ route('profile.show', $path->curator) }}">{{ $path->curator->name }}</a> <x-verified-badge :user="$path->curator" /></dd>
                </dl>
            </div>
        </aside>
    </div>
</div>
@endsection

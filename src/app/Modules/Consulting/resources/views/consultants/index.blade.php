@extends('layouts.app')
@section('title', 'Consultants')
@section('subtitle', 'Consulting')

@section('content')
<x-page-header title="Consultants" subtitle="Verified experts who offer one-to-one sessions: project reviews, career advice, technical help.">
    @can('offer', \App\Modules\Consulting\Models\ConsultantProfile::class)
        <x-slot:actions>
            <a href="{{ route('consulting.profile') }}" class="btn btn-quiet"><i class="bi bi-person-gear me-1" aria-hidden="true"></i> My consultant profile</a>
            <a href="{{ route('consulting.availability') }}" class="btn btn-accent"><i class="bi bi-calendar-week me-1" aria-hidden="true"></i> My availability</a>
        </x-slot:actions>
    @endcan
</x-page-header>

<form method="GET" action="{{ route('consultants.index') }}" class="d-flex gap-2 mb-4" role="search">
    <label for="consultant_q" class="visually-hidden">Search consultants</label>
    <input type="search" id="consultant_q" name="q" value="{{ $q }}" class="form-control" placeholder="Search by name, topic or headline…">
    <button type="submit" class="btn btn-soft">Search</button>
</form>

@if ($profiles->isEmpty())
    <div class="card"><x-empty icon="person-video3" :title="$q !== '' ? 'No consultants match “'.$q.'”' : 'No consultants yet'" /></div>
@else
    <div class="row g-3">
        @foreach ($profiles as $profile)
            @php $soonest = $next[$profile->id]; @endphp
            <div class="col-md-6 col-xl-4">
                <article class="card h-100 aq-article-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <x-avatar :user="$profile->user" size="md" />
                            <div class="min-w-0">
                                <h2 class="aq-card-title mb-0"><a href="{{ route('consultants.show', $profile) }}" class="stretched-link text-decoration-none">{{ $profile->user->name }}</a></h2>
                                <span class="aq-text-xs aq-muted">{{ $profile->session_minutes }}-minute sessions · {{ $profile->priceLabel() }}</span>
                            </div>
                        </div>
                        <p class="aq-text-sm aq-ink-2 flex-grow-1">{{ $profile->headline }}</p>
                        @if ($profile->expertise)
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                @foreach (array_slice($profile->expertise, 0, 4) as $topic)<span class="badge text-bg-light">{{ $topic }}</span>@endforeach
                            </div>
                        @endif
                        <p class="mb-0 aq-text-xs {{ $soonest ? 'aq-muted' : 'aq-danger' }}">
                            <i class="bi bi-{{ $soonest ? 'calendar-check' : 'calendar-x' }}" aria-hidden="true"></i>
                            {{ $soonest ? 'Next free: '.$soonest->format('D j M, H:i T') : 'No free times in the next '.$profile->window_days.' days' }}
                        </p>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
    <nav aria-label="Consultant pages" class="mt-4">{{ $profiles->links() }}</nav>
@endif
@endsection

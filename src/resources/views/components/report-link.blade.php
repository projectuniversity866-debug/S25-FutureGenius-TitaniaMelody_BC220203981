{{-- "Report" link for content that breaks the guidelines (Governance, roadmap 2.5).
     <x-report-link :item="$answer" />  — $item implements Platform\Contracts\Reportable.
     Shown to signed-in members, never on their own content. --}}
@props(['item'])

@auth
    @unless ($item->reportAuthor()?->is(auth()->user()))
        <a href="{{ route('reports.create', [$item->getMorphClass(), $item->getKey()]) }}" {{ $attributes->class('aq-report-link') }}>
            <i class="bi bi-flag" aria-hidden="true"></i> Report<span class="visually-hidden"> this {{ str_replace('_', ' ', $item->getMorphClass()) }}</span>
        </a>
    @endunless
@endauth

{{-- A titled card section. The title becomes the section's accessible name.

     <x-card title="Recent questions" icon="patch-question" flush>
         <x-slot:actions><a href="…">View all</a></x-slot:actions>
         <ul class="list-group list-group-flush">…</ul>
     </x-card>

     `flush` drops the body padding, for list groups and tables that run to
     the card's edges. --}}
@props(['title' => null, 'icon' => null, 'flush' => false])

@php $headingId = $title ? 'card-'.\Illuminate\Support\Str::slug($title).'-'.\Illuminate\Support\Str::random(4) : null; @endphp

<section {{ $attributes->class('card') }} @if ($headingId) aria-labelledby="{{ $headingId }}" @endif>
    @if ($title)
        <div class="card-header aq-card-head">
            <h2 id="{{ $headingId }}" class="aq-h3">
                @if ($icon)<i class="bi bi-{{ $icon }} aq-accent" aria-hidden="true"></i>@endif
                {{ $title }}
            </h2>
            @isset($actions){{ $actions }}@endisset
        </div>
    @endif

    @if ($flush)
        {{ $slot }}
    @else
        <div class="card-body">{{ $slot }}</div>
    @endif
</section>

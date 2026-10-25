{{-- The heading block at the top of every page.

     <x-page-header title="Manage users" subtitle="Assign roles…"
                    :breadcrumbs="['Jobs' => route('jobs.index'), 'Data Analyst Intern' => null]">
         <x-slot:actions><a class="btn btn-accent" …>New</a></x-slot:actions>
     </x-page-header> --}}
@props(['title', 'subtitle' => null, 'breadcrumbs' => []])

<div {{ $attributes->class('aq-page-head') }}>
    <div class="min-w-0">
        @if ($breadcrumbs)
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb">
                    @foreach ($breadcrumbs as $label => $url)
                        @if ($url)
                            <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif

        <h1 class="aq-h1">{{ $title }}</h1>

        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif

        {{ $slot }}
    </div>

    @isset($actions)
        <div class="aq-page-head-actions">{{ $actions }}</div>
    @endisset
</div>

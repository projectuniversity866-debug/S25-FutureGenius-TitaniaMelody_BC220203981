{{-- "Verified expert" mark (Knowledge, roadmap 2.1) next to a name.
     <x-verified-badge :user="$answer->user" />  ·  add `label` to show the words too. --}}
@props(['user', 'label' => false])

@php $verification = $user?->expertVerification; @endphp

@if ($verification)
    <span {{ $attributes->class('aq-verified') }} title="Verified expert in {{ $verification->field }}">
        <i class="bi bi-patch-check-fill" aria-hidden="true"></i>
        @if ($label)
            <span>Verified expert · {{ $verification->field }}</span>
        @else
            <span class="visually-hidden">Verified expert in {{ $verification->field }}</span>
        @endif
    </span>
@endif

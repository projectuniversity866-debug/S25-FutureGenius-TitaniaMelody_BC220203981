{{-- Monogram for an organisation (employer, university…). The tint is
     derived from the name, so one organisation always gets the same colour.
     <x-org-mark name="CodeCraft Solutions" size="lg" /> --}}
@props(['name', 'size' => 'md'])

@php
    $words = preg_split('/\s+/', trim($name)) ?: [''];
    $initials = mb_strtoupper(mb_substr($words[0], 0, 1).(isset($words[1]) ? mb_substr($words[1], 0, 1) : ''));
    $tones = ['accent', 'blue', 'amber', 'green', 'red'];
    $tone = $tones[crc32($name) % count($tones)];
@endphp

<span aria-hidden="true" {{ $attributes->class(['aq-org-mark', "is-$tone", 'aq-org-mark-lg' => $size === 'lg']) }}>{{ $initials }}</span>

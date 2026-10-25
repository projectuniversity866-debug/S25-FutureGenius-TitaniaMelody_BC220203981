{{-- Coloured pill for a workflow status, so every screen shows the same
     colour for the same state.  <x-status :value="$application->status" /> --}}
@props(['value'])

@php
    $tone = [
        'pending'     => 'secondary',
        'reviewed'    => 'primary',
        'shortlisted' => 'info',
        'accepted'    => 'success',
        'rejected'    => 'danger',
        'active'      => 'success',
        'inactive'    => 'secondary',
        'open'        => 'success',
        'closed'      => 'secondary',
        'published'   => 'success',
        'unpublished' => 'secondary',
        'expired'     => 'danger',
    ][$value] ?? 'light';
@endphp

<span {{ $attributes->class(['badge', "text-bg-$tone"]) }}>{{ ucfirst($value) }}</span>

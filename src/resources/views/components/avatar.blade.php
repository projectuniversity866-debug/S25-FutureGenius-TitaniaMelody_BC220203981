{{-- User photo, or their initials when there is none.
     <x-avatar :user="$user" size="xs|sm|md|lg|xl" /> --}}
@props(['user', 'size' => 'md'])

@if ($url = $user->avatarUrl())
    <img src="{{ $url }}" alt="Profile photo of {{ $user->name }}"
         {{ $attributes->class(['aq-avatar', "aq-avatar-$size"]) }}>
@else
    <span role="img" aria-label="{{ $user->name }}"
          {{ $attributes->class(['aq-avatar', "aq-avatar-$size", 'aq-avatar-fallback']) }}>{{ $user->initials() }}</span>
@endif

@php
    $size = $size ?? 40;
    $url  = $user->avatarUrl();
@endphp

@if ($url)
    <img src="{{ $url }}" alt="Profile photo of {{ $user->name }}"
         class="rounded-circle object-fit-cover flex-shrink-0"
         width="{{ $size }}" height="{{ $size }}"
         style="width: {{ $size }}px; height: {{ $size }}px;">
@else
    <span class="rounded-circle aq-avatar-fallback d-inline-flex align-items-center justify-content-center fw-semibold flex-shrink-0"
          style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ max(10, (int) ($size * 0.38)) }}px; letter-spacing: -0.02em;"
          role="img" aria-label="Profile placeholder for {{ $user->name }}">
        {{ $user->initials() }}
    </span>
@endif

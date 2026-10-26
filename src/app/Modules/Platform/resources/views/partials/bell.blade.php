{{-- Notification bell in the top bar: unread count + the latest five.
     resources/js/live-notifications.js keeps it current without a reload
     (roadmap 1.11), using the data-aq-bell hooks below. --}}
@php $unread = auth()->user()->unreadNotifications()->count(); @endphp

<div class="dropdown" data-aq-bell data-url="{{ route('notifications.summary') }}" data-unread="{{ $unread }}">
    {{-- auto-close "outside": "Mark all read" inside the menu keeps it open. --}}
    <button type="button" class="aq-icon-btn aq-bell" data-bs-toggle="dropdown" data-bs-auto-close="outside"
            aria-expanded="false" data-aq-bell-button
            aria-label="Notifications{{ $unread ? ', '.$unread.' unread' : '' }}">
        <i class="bi bi-bell" aria-hidden="true"></i>
        <span class="aq-bell-count" aria-hidden="true" data-aq-bell-count @if (! $unread) hidden @endif>{{ $unread > 9 ? '9+' : $unread }}</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end aq-bell-menu" data-aq-bell-menu>
        @include('platform::partials.bell-menu', ['unread' => $unread])
    </div>
</div>

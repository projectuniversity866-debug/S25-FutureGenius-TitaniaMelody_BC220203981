{{-- Notification bell in the top bar: unread count + the latest five. --}}
@php
    $unread = auth()->user()->unreadNotifications()->count();
    $latest = auth()->user()->notifications()->take(5)->get();
@endphp

<div class="dropdown">
    <button type="button" class="aq-icon-btn aq-bell" data-bs-toggle="dropdown" aria-expanded="false"
            aria-label="Notifications{{ $unread ? ', '.$unread.' unread' : '' }}">
        <i class="bi bi-bell" aria-hidden="true"></i>
        @if ($unread)
            <span class="aq-bell-count" aria-hidden="true">{{ $unread > 9 ? '9+' : $unread }}</span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end aq-bell-menu">
        <div class="aq-bell-head">
            <span class="aq-strong">Notifications</span>
            @if ($unread)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm p-0">Mark all read</button>
                </form>
            @endif
        </div>
        @forelse ($latest as $n)
            <a href="{{ route('notifications.open', $n->id) }}" @class(['aq-bell-item', 'is-unread' => ! $n->read_at])>
                <i class="bi bi-{{ $n->data['icon'] ?? 'bell' }}" aria-hidden="true"></i>
                <span class="min-w-0">
                    <strong>{{ $n->data['title'] ?? 'Notification' }}</strong>
                    <span class="d-block text-truncate">{{ $n->data['body'] ?? '' }}</span>
                    <small>{{ $n->created_at->diffForHumans() }}@unless ($n->read_at) · <span class="aq-accent-ink">new</span>@endunless</small>
                </span>
            </a>
        @empty
            <p class="aq-bell-empty">You are all caught up.</p>
        @endforelse
        <a href="{{ route('notifications.index') }}" class="aq-bell-all">View all notifications</a>
    </div>
</div>

@extends('layouts.app')
@section('title', 'Notifications')
@section('subtitle', 'Updates about your questions and applications')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Notifications" subtitle="Answers to your questions, application updates and more.">
    @if (auth()->user()->unreadNotifications()->exists())
        <x-slot:actions>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-quiet btn-sm">
                    <i class="bi bi-check2-all me-1" aria-hidden="true"></i> Mark all read
                </button>
            </form>
        </x-slot:actions>
    @endif
</x-page-header>

<div class="card">
    <ul class="list-group list-group-flush">
        @forelse ($notifications as $n)
            <li @class(['list-group-item', 'aq-notification', 'is-unread' => ! $n->read_at])>
                <span class="aq-notification-icon" aria-hidden="true"><i class="bi bi-{{ $n->data['icon'] ?? 'bell' }}"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <a href="{{ route('notifications.open', $n->id) }}" class="fw-semibold text-decoration-none">
                        {{ $n->data['title'] ?? 'Notification' }}
                    </a>
                    @unless ($n->read_at)<span class="badge text-bg-info ms-1">New</span>@endunless
                    <p class="mb-0 aq-text-sm aq-ink-2">{{ $n->data['body'] ?? '' }}</p>
                    <time class="aq-text-xs aq-muted" datetime="{{ $n->created_at->toIso8601String() }}">{{ $n->created_at->diffForHumans() }}</time>
                </div>
            </li>
        @empty
            <li class="list-group-item">
                <x-empty icon="bell" title="No notifications yet"
                         message="You will be told here when someone answers your question or updates your application." />
            </li>
        @endforelse
    </ul>
</div>

<nav aria-label="Notification pages" class="mt-4">{{ $notifications->links() }}</nav>
@endsection

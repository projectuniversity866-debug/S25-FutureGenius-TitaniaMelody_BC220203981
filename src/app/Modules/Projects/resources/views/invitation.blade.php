@extends('layouts.app')
@section('title', 'Project invitation')
@section('subtitle', 'Projects')
@section('container', 'aq-container-narrow')

@section('content')
@php
    $project = $invitation->project;
    $role = \App\Modules\Projects\Models\Project::ROLES[$invitation->role];
@endphp
<x-page-header title="Project invitation" />

<div class="card">
    <div class="card-body p-4">
        <p class="aq-label mb-1">{{ $invitation->inviter?->name ?? 'A member' }} invited {{ $invitation->email }} to</p>
        <h2 class="aq-h2 mb-1">{{ $project->name }}</h2>
        <p class="aq-text-sm aq-muted">As <strong>{{ $role[0] }}</strong>: {{ mb_strtolower($role[1]) }}.</p>
        @if ($project->description)
            <div class="prose aq-text-sm mb-3">{{ \Illuminate\Support\Str::limit($project->description, 400) }}</div>
        @endif

        @error('invitation')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror

        @if ($invitation->isOpen())
            <p class="aq-text-xs aq-muted">Valid until {{ $invitation->expires_at->format('d M Y, H:i') }}.</p>
            <div class="d-flex flex-wrap gap-2">
                @auth
                    <form method="POST" action="{{ route('invitations.accept', $token) }}">
                        @csrf
                        <button type="submit" class="btn btn-accent">Accept and join</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-accent">Sign in to accept</a>
                    <a href="{{ route('register') }}" class="btn btn-quiet">Create an account with {{ $invitation->email }}</a>
                @endauth
                <form method="POST" action="{{ route('invitations.decline', $token) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">Decline</button>
                </form>
            </div>
        @else
            <x-empty icon="envelope-x"
                     :title="$invitation->status === 'pending' ? 'This invitation has expired' : 'This invitation was '.$invitation->status"
                     :message="$invitation->status === 'accepted' ? null : 'Ask the project owner to send a new one if you still want to join.'"
                     :action="$invitation->status === 'accepted' && auth()->check() ? ['url' => route('projects.show', $project), 'label' => 'Open the project'] : null" />
        @endif
    </div>
</div>
@endsection

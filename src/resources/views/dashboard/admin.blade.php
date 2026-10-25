@extends('layouts.app')
@section('title', 'Dashboard')
@section('subtitle', 'Platform overview')

@section('content')
@include('dashboard._welcome')

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <x-card title="Community activity" icon="graph-up" class="h-100">
            <x-slot:actions><span class="aq-text-xs aq-muted">Last {{ $days }} days</span></x-slot:actions>
            <x-line-chart :labels="$activity['labels']" :series="$activity['series']"
                          :label="'Questions and answers posted per day, last '.$days.' days'" />
        </x-card>
    </div>
    <div class="col-xl-4">
        <x-card title="Questions by topic" icon="tags" class="h-100">
            <x-bar-list :items="$topics" noun="question" empty="No questions yet" />
        </x-card>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6 col-xl-4">
        <x-card title="Applications by status" icon="kanban" class="h-100">
            <x-bar-list :items="$pipeline" noun="application" empty="No applications yet" />
        </x-card>
    </div>

    <div class="col-lg-6 col-xl-4">
        <x-card title="Needs an answer" icon="hourglass-split" flush class="h-100">
            <x-slot:actions><a href="{{ route('questions.index', ['sort' => 'unanswered']) }}">All unanswered</a></x-slot:actions>
            <ul class="list-group list-group-flush">
                @forelse ($unanswered as $question)
                    <li class="list-group-item">
                        <a href="{{ route('questions.show', $question) }}"
                           class="fw-semibold text-decoration-none d-block">{{ $question->title }}</a>
                        <span class="aq-text-xs aq-muted">
                            {{ $question->category?->name ?? 'Uncategorised' }} · {{ $question->user->name }}
                        </span>
                        <span class="badge text-bg-warning ms-1">
                            Waiting {{ $question->created_at->diffForHumans(null, true) }}
                        </span>
                    </li>
                @empty
                    <li class="list-group-item">
                        <x-empty icon="check2-all" title="Every open question has an answer" />
                    </li>
                @endforelse
            </ul>
        </x-card>
    </div>

    <div class="col-lg-12 col-xl-4">
        <x-card title="Newest members" icon="person-plus" flush class="h-100">
            <x-slot:actions><a href="{{ route('admin.users.index') }}">Manage users</a></x-slot:actions>
            <ul class="list-group list-group-flush">
                @foreach ($newMembers as $member)
                    <li class="list-group-item d-flex align-items-center gap-2">
                        <x-avatar :user="$member" size="sm" />
                        <div class="min-w-0 flex-grow-1">
                            <a href="{{ route('profile.show', $member) }}"
                               class="fw-semibold text-decoration-none d-block text-truncate">{{ $member->name }}</a>
                            <span class="aq-text-xs aq-muted">{{ $member->roleName() }} · joined {{ $member->created_at->diffForHumans() }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>
</div>
@endsection

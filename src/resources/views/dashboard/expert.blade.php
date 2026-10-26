@extends('layouts.app')
@section('title', 'Dashboard')
@section('subtitle', 'Your answers and opportunities')

@section('content')
@include('dashboard._welcome')

<div class="row g-4">
    <div class="col-xl-8">
        <x-card title="Questions you could answer" icon="lightbulb" flush class="mb-4">
            <x-slot:actions><a href="{{ route('questions.index', ['sort' => 'unanswered']) }}">All unanswered</a></x-slot:actions>
            <ul class="list-group list-group-flush">
                @forelse ($unanswered as $question)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('questions.show', $question) }}"
                                   class="fw-semibold text-decoration-none d-block text-truncate">{{ $question->title }}</a>
                                <span class="aq-text-xs aq-muted">
                                    {{ $question->category?->name ?? 'Uncategorised' }} · asked {{ $question->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <a href="{{ route('questions.show', $question) }}#answer_body" class="btn btn-soft btn-sm flex-shrink-0">
                                Answer<span class="visually-hidden"> “{{ $question->title }}”</span>
                            </a>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item">
                        <x-empty icon="check2-all" title="No unanswered questions right now" />
                    </li>
                @endforelse
            </ul>
        </x-card>

        <x-card title="Latest applicants" icon="people" flush>
            <x-slot:actions><a href="{{ route('jobs.mine') }}">My postings</a></x-slot:actions>
            <ul class="list-group list-group-flush">
                @forelse ($recentApplicants as $application)
                    <li class="list-group-item d-flex align-items-center gap-3">
                        <x-avatar :user="$application->user" size="sm" />
                        <div class="min-w-0 flex-grow-1">
                            <span class="fw-semibold d-block text-truncate">{{ $application->user->name }}</span>
                            <a href="{{ route('jobs.applicants', $application->jobPost) }}" class="aq-text-xs text-decoration-none">
                                {{ $application->jobPost->title }}
                            </a>
                            <span class="aq-text-xs aq-muted"> · {{ $application->created_at->diffForHumans() }}</span>
                        </div>
                        <x-status :value="$application->status" class="flex-shrink-0" />
                    </li>
                @empty
                    <li class="list-group-item">
                        <x-empty icon="inbox" title="No applicants yet"
                                 :action="['url' => route('jobs.create'), 'label' => 'Post an opportunity']" />
                    </li>
                @endforelse
            </ul>
        </x-card>
    </div>

    <div class="col-xl-4">
        <x-card title="Applicants per opportunity" icon="bar-chart" class="mb-4">
            <x-bar-list :items="$applicantsPerJob" noun="applicant" empty="No applicants yet" />
        </x-card>

        <x-card title="Hiring pipeline" icon="kanban">
            <x-bar-list :items="$pipeline" noun="application" empty="No applications yet" />
        </x-card>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Dashboard')
@section('subtitle', 'Your activity across AQDesk')

@section('content')
@include('dashboard._welcome')

<div class="row g-4">
    <div class="col-xl-8">
        <x-card title="My questions" icon="patch-question" flush class="mb-4">
            <x-slot:actions><a href="{{ route('profile.show', $user) }}">All my questions</a></x-slot:actions>
            <ul class="list-group list-group-flush">
                @forelse ($myQuestions as $question)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <a href="{{ route('questions.show', $question) }}"
                           class="text-decoration-none text-truncate">{{ $question->title }}</a>
                        <span class="badge {{ $question->answers_count ? 'text-bg-success' : 'text-bg-light' }} flex-shrink-0">
                            {{ $question->answers_count }} {{ \Illuminate\Support\Str::plural('answer', $question->answers_count) }}
                        </span>
                    </li>
                @empty
                    <li class="list-group-item">
                        <x-empty icon="pencil" title="You have not asked anything yet"
                                 message="Your questions will appear here."
                                 :action="['url' => route('questions.create'), 'label' => 'Ask your first question']" />
                    </li>
                @endforelse
            </ul>
        </x-card>

        <x-card title="Community activity" icon="graph-up">
            <x-slot:actions><span class="aq-text-xs aq-muted">Last {{ $days }} days</span></x-slot:actions>
            <x-line-chart :labels="$activity['labels']" :series="$activity['series']"
                          :label="'Questions and answers posted per day, last '.$days.' days'" />
        </x-card>
    </div>

    <div class="col-xl-4">
        <x-card title="My applications" icon="send" flush class="mb-4">
            <x-slot:actions><a href="{{ route('applications.index') }}">View all</a></x-slot:actions>
            <ul class="list-group list-group-flush">
                @forelse ($myApplications as $application)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <span class="text-truncate">{{ $application->jobPost?->title ?? 'Removed opportunity' }}</span>
                        <x-status :value="$application->status" class="flex-shrink-0" />
                    </li>
                @empty
                    <li class="list-group-item">
                        <x-empty icon="send" title="No applications yet"
                                 message="Apply from the jobs board to track them here."
                                 :action="['url' => route('jobs.index'), 'label' => 'Browse opportunities']" />
                    </li>
                @endforelse
            </ul>
        </x-card>

        <x-card title="Opportunities for you" icon="briefcase" flush>
            <x-slot:actions><a href="{{ route('jobs.index') }}">All opportunities</a></x-slot:actions>
            <ul class="list-group list-group-flush">
                @forelse ($recommendedJobs as $job)
                    <li class="list-group-item">
                        <div class="d-flex align-items-center gap-3">
                            <x-org-mark :name="$job->company" />
                            <div class="min-w-0">
                                <a href="{{ route('jobs.show', $job) }}"
                                   class="fw-semibold text-decoration-none d-block text-truncate">{{ $job->title }}</a>
                                <span class="aq-text-xs aq-muted">{{ $job->company }} · {{ ucfirst($job->type) }}</span>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item">
                        <x-empty icon="briefcase" title="You have applied to every open opportunity" />
                    </li>
                @endforelse
            </ul>
        </x-card>
    </div>
</div>
@endsection

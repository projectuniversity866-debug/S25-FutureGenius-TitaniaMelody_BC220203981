@extends('layouts.app')
@section('title', 'Saved')
@section('subtitle', 'Things you put aside to read later')

@section('content')
<x-page-header title="Saved"
               subtitle="Questions and opportunities you saved with the bookmark button, most recent first. Only you can see this list." />

<x-tabs label="Saved items" :items="[
    ['label' => 'Questions', 'url' => route('saved.index'), 'active' => $tab === 'questions', 'count' => $counts['questions']],
    ['label' => 'Opportunities', 'url' => route('saved.index', ['tab' => 'opportunities']), 'active' => $tab === 'opportunities', 'count' => $counts['opportunities']],
]" />

@if ($tab === 'questions')
    @forelse ($items as $question)
        @include('community::partials.question-card', ['question' => $question, 'savedAt' => $question->saved_at])
    @empty
        <div class="card">
            <x-empty icon="bookmark" title="No saved questions"
                     message="Use the bookmark under a question’s vote buttons to keep it here for later."
                     :action="['url' => route('questions.index'), 'label' => 'Browse questions']" />
        </div>
    @endforelse
@else
    <div class="row g-3">
        @forelse ($items as $job)
            @include('careers::partials.job-card', ['job' => $job])
        @empty
            <div class="col-12">
                <div class="card">
                    <x-empty icon="bookmark" title="No saved opportunities"
                             message="Use the bookmark on an opportunity’s page to keep it here — handy for deadlines."
                             :action="['url' => route('jobs.index'), 'label' => 'Browse opportunities']" />
                </div>
            </div>
        @endforelse
    </div>
@endif

<nav aria-label="Saved item pages" class="mt-4">{{ $items->links() }}</nav>
@endsection

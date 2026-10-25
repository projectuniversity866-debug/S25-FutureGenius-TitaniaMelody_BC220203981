@extends('layouts.app')
@section('title', 'Saved')
@section('subtitle', 'Questions you put aside to read later')

@section('content')
<x-page-header title="Saved"
               subtitle="Questions you saved with the bookmark button, most recent first. Only you can see this list." />

@forelse ($questions as $question)
    @include('community::partials.question-card', ['question' => $question, 'savedAt' => $question->saved_at])
@empty
    <div class="card">
        <x-empty icon="bookmark" title="Nothing saved yet"
                 message="Use the bookmark under a question’s vote buttons to keep it here for later."
                 :action="['url' => route('questions.index'), 'label' => 'Browse questions']" />
    </div>
@endforelse

<nav aria-label="Saved question pages" class="mt-4">{{ $questions->links() }}</nav>
@endsection

@extends('layouts.app')
@section('title', 'Ask a question')
@section('subtitle', 'Community Q&A')

@section('content')
<div class="aq-page-head">
    <div>
        <h1>Ask the community</h1>
        <p>Good questions get answered faster. Be specific and add context.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('questions.store') }}" novalidate>
                    @include('questions._form')

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-accent">Post question</button>
                        <a href="{{ route('questions.index') }}" class="btn btn-quiet">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <aside class="card aq-card-quiet" aria-labelledby="tips-heading">
            <div class="card-body">
                <h2 id="tips-heading" class="aq-label mb-2">Writing a good question</h2>
                <ul class="mb-0 ps-3 d-grid gap-1" style="font-size: .8125rem; color: var(--aq-muted);">
                    <li>Summarise the problem in the title.</li>
                    <li>Explain what you already tried.</li>
                    <li>Pick the category that fits best.</li>
                    <li>Add tags so experts can find it.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection

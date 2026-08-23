@extends('layouts.app')
@section('title', 'Ask a question')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h4 fw-bold mb-3">Ask the community</h1>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('questions.store') }}" novalidate>
                    @include('questions._form')

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-info fw-semibold">Post question</button>
                        <a href="{{ route('questions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <aside class="card border-0 bg-body-secondary" aria-labelledby="tips-heading">
            <div class="card-body">
                <h2 id="tips-heading" class="h6 fw-bold">Writing a good question</h2>
                <ul class="small text-muted mb-0 ps-3">
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

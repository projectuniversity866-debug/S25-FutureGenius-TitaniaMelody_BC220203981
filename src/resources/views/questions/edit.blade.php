@extends('layouts.app')
@section('title', 'Edit question')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h4 fw-bold mb-3">Edit question</h1>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('questions.update', $question) }}" novalidate>
                    @include('questions._form')

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-info fw-semibold">Save changes</button>
                        <a href="{{ route('questions.show', $question) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

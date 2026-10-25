@extends('layouts.app')
@section('title', 'Edit question')
@section('subtitle', 'Community Q&A')
@section('container', 'aq-container-narrow')

@section('content')
<div class="aq-page-head">
    <div><h1>Edit question</h1></div>
</div>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('questions.update', $question) }}" novalidate>
            @include('questions._form')

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent">Save changes</button>
                <a href="{{ route('questions.show', $question) }}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Edit question')
@section('subtitle', 'Community Q&A')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Edit question"
               :breadcrumbs="['Community Q&A' => route('questions.index'), $question->title => route('questions.show', $question), 'Edit' => null]" />


<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('questions.update', $question) }}" novalidate>
            @include('community::questions._form')

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent">Save changes</button>
                <a href="{{ route('questions.show', $question) }}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

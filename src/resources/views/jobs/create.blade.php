@extends('layouts.app')
@section('title', 'Post an opportunity')
@section('subtitle', 'Jobs & opportunities')

@section('content')
<div class="aq-page-head">
    <div>
        <h1>Post a job or internship</h1>
        <p>Visible on the board as soon as you publish.</p>
    </div>
</div>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('jobs.store') }}" novalidate>
            @include('jobs._form')

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-accent">Publish opportunity</button>
                <a href="{{ route('jobs.index') }}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Edit opportunity')
@section('subtitle', 'Jobs & opportunities')

@section('content')
<div class="aq-page-head">
    <div><h1>Edit opportunity</h1></div>
</div>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('jobs.update', $job) }}" novalidate>
            @include('jobs._form')

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-accent">Save changes</button>
                <a href="{{ route('jobs.show', $job) }}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

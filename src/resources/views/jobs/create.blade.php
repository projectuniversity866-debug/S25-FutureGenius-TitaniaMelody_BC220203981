@extends('layouts.app')
@section('title', 'Post an opportunity')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <h1 class="h4 fw-bold mb-3">Post a job or internship</h1>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('jobs.store') }}" novalidate>
                    @include('jobs._form')

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-info fw-semibold">Publish opportunity</button>
                        <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

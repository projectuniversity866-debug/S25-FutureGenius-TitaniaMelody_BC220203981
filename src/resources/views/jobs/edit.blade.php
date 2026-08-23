@extends('layouts.app')
@section('title', 'Edit opportunity')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <h1 class="h4 fw-bold mb-3">Edit opportunity</h1>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('jobs.update', $job) }}" novalidate>
                    @include('jobs._form')

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-info fw-semibold">Save changes</button>
                        <a href="{{ route('jobs.show', $job) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

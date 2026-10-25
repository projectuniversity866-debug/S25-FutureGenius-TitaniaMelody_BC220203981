@extends('layouts.app')
@section('title', 'Edit opportunity')
@section('subtitle', 'Jobs & opportunities')

@section('content')
<x-page-header title="Edit opportunity"
               :breadcrumbs="['Jobs & opportunities' => route('jobs.index'), $job->title => route('jobs.show', $job), 'Edit' => null]" />


<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('jobs.update', $job) }}" novalidate>
            @include('careers::jobs._form')

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-accent">Save changes</button>
                <a href="{{ route('jobs.show', $job) }}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Post an opportunity')
@section('subtitle', 'Jobs & opportunities')

@section('content')
<x-page-header title="Post a job or internship" subtitle="Visible on the board as soon as you publish."
               :breadcrumbs="['Jobs & opportunities' => route('jobs.index'), 'Post an opportunity' => null]" />


<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('jobs.store') }}" novalidate>
            @include('careers::jobs._form')

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-accent">Publish opportunity</button>
                <a href="{{ route('jobs.index') }}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

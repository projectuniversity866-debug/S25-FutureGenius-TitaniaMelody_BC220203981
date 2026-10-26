@extends('layouts.app')
@section('title', 'New learning path')
@section('subtitle', 'Learning Hub')

@section('content')
<x-page-header title="New learning path" subtitle="Give it a title and summary first; you add the steps on the next screen."
               :breadcrumbs="['Learning Hub' => route('learning.index'), 'New path' => null]" />

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('learning.paths.store') }}" novalidate>
            @csrf
            @include('knowledge::learning._path-fields', ['path' => null])
            <button type="submit" class="btn btn-accent mt-4">Create and add steps</button>
        </form>
    </div>
</div>
@endsection

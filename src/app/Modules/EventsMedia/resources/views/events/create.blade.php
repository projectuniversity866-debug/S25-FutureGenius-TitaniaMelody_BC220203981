@extends('layouts.app')
@section('title', 'New event')
@section('subtitle', 'Events')

@section('content')
<x-page-header title="New event" subtitle="It is saved as a draft; publish it when the details are right."
               :breadcrumbs="['Events' => route('events.index'), 'New event' => null]" />

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('events.store') }}" novalidate>
            @csrf
            @include('events-media::events._form', ['event' => null])
            <button type="submit" class="btn btn-accent">Save draft</button>
            <a href="{{ route('events.index') }}" class="btn btn-quiet">Cancel</a>
        </form>
    </div>
</div>
@endsection

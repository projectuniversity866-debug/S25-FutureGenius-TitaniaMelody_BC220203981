@extends('layouts.app')
@section('title', 'Edit event')
@section('subtitle', $event->title)

@section('content')
<x-page-header title="Edit event" :subtitle="$event->title"
               :breadcrumbs="['Events' => route('events.index'), $event->title => route('events.show', $event), 'Edit' => null]" />

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('events.update', $event) }}" novalidate>
            @csrf
            @method('PUT')
            @include('events-media::events._form')
            <button type="submit" class="btn btn-accent">Save event</button>
            <a href="{{ route('events.show', $event) }}" class="btn btn-quiet">Cancel</a>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', $title)
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header :title="$title" />

<x-card>
    <p role="status"><i class="bi bi-{{ $icon }} me-1" aria-hidden="true"></i> {{ $text }}</p>
    <a href="{{ route('newsletter.show') }}" class="btn btn-soft btn-sm">Newsletter page</a>
</x-card>
@endsection

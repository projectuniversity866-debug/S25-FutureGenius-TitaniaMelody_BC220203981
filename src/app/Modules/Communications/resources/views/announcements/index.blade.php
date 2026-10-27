@extends('layouts.app')
@section('title', 'Updates')
@section('container', 'aq-container-narrow')

@section('content')
@php use App\Modules\Communications\Models\Announcement; @endphp
<x-page-header title="Updates" subtitle="Announcements from the AQDesk team.">
    <x-slot:actions>
        <a href="{{ route('newsletter.show') }}" class="btn btn-quiet"><i class="bi bi-envelope-paper me-1" aria-hidden="true"></i> Get the newsletter</a>
    </x-slot:actions>
</x-page-header>

<h2 class="aq-h3">Now</h2>
@forelse ($current as $a)
    @include('communications::announcements._item', ['a' => $a])
@empty
    <div class="card mb-4"><x-empty icon="megaphone" title="No announcements right now" /></div>
@endforelse

@if ($past->isNotEmpty())
    <h2 class="aq-h3 mt-4">Earlier</h2>
    @foreach ($past as $a)
        @include('communications::announcements._item', ['a' => $a])
    @endforeach
@endif
@endsection

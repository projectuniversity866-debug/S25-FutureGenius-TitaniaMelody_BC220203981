@extends('layouts.app')
@section('title', 'Write for the Knowledge Hub')
@section('subtitle', 'Knowledge Hub')

@section('content')
<x-page-header title="Write for the Knowledge Hub" subtitle="Markdown is supported. Save a draft any time; publish when it is ready."
               :breadcrumbs="['Knowledge Hub' => route('articles.index'), 'Write' => null]" />

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('articles.store') }}" novalidate>
            @include('knowledge::articles._form')
        </form>
    </div>
</div>
@endsection

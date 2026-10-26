@extends('layouts.app')
@section('title', 'Edit article')
@section('subtitle', 'Knowledge Hub')

@section('content')
<x-page-header title="Edit article"
               :breadcrumbs="['Knowledge Hub' => route('articles.index'), $article->title => route('articles.show', $article), 'Edit' => null]" />

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('articles.update', $article) }}" novalidate>
            @include('knowledge::articles._form')
        </form>
    </div>
</div>
@endsection

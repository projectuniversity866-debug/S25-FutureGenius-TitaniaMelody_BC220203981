@extends('layouts.app')
@section('title', 'Thank you')
@section('subtitle', $form->title)
@section('container', 'aq-container-narrow')

@section('content')
<div class="card">
    <div class="card-body p-4">
        <x-empty icon="check2-circle" title="Response received"
                 :message="$form->confirmation_message ?: 'Thank you for filling in “'.$form->title.'”.'"
                 :action="['url' => route('home'), 'label' => 'Back to AQDesk']" />
    </div>
</div>
@endsection

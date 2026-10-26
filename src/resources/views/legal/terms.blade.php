@extends('layouts.app')
@section('title', 'Terms of use')
@section('subtitle', 'The agreement between you and AQDesk')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Terms of use"
               :subtitle="'Version '.\App\Modules\Governance\Support\LegalDocuments::VERSION.' · last updated '.\App\Modules\Governance\Support\LegalDocuments::UPDATED" />

<div class="card">
    <div class="card-body p-4 aq-legal">
        <h2>1. Your account</h2>
        <p>You are responsible for your account and for keeping your password private. Give accurate information; one person, one account.</p>

        <h2>2. What you post</h2>
        <p>
            You keep ownership of what you write. By posting it you allow AQDesk to show it to other members and visitors, and to keep it
            visible — credited to “Deleted user” — if you later delete your account, so that discussions stay readable.
            Everything you post must follow the <a href="{{ route('guidelines') }}">community guidelines</a>.
        </p>

        <h2>3. Opportunities and applications</h2>
        <p>
            Organisations that publish opportunities are responsible for them being real and lawful. AQDesk is not a party to any job,
            scholarship or grant agreement. CVs you upload are shared only with the publisher of the opportunity you apply to.
        </p>

        <h2>4. Moderation</h2>
        <p>Moderators may remove content that breaks these terms or the guidelines, and may deactivate accounts that do so repeatedly. Decisions are logged.</p>

        <h2>5. Your data</h2>
        <p>How your data is used and kept is described in the <a href="{{ route('privacy') }}">privacy policy</a>. You can download it or delete your account at any time.</p>

        <h2>6. Changes</h2>
        <p class="mb-0">When these terms change, the version number goes up and you will be asked to accept the new version the next time you sign in.</p>
    </div>
</div>
@endsection

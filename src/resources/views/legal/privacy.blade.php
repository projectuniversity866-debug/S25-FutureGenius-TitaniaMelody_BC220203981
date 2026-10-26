@extends('layouts.app')
@section('title', 'Privacy policy')
@section('subtitle', 'What we collect and why')
@section('container', 'aq-container-narrow')

@section('content')
@php use App\Modules\Governance\Support\LegalDocuments; @endphp
<x-page-header title="Privacy policy" :subtitle="'Version '.LegalDocuments::VERSION.' · last updated '.LegalDocuments::UPDATED" />

<div class="card">
    <div class="card-body p-4 aq-legal">
        <h2>What we collect</h2>
        <ul>
            <li><strong>Account:</strong> your name, email address, password (stored only as a secure hash), role and settings.</li>
            <li><strong>Profile:</strong> anything you add — headline, bio, skills, links, photo.</li>
            <li><strong>What you do:</strong> questions, answers, comments, votes, articles, applications, saved items, alerts, learning progress, form responses.</li>
            <li><strong>Security:</strong> where you are signed in (browser and IP address) and an audit log of administrative actions.</li>
        </ul>

        <h2>Why we use it</h2>
        <p>Only to run AQDesk: to show your contributions, send the notifications you choose, let opportunity publishers review your applications, and keep the platform secure. We do not sell data or show adverts.</p>

        <h2>Who can see it</h2>
        <ul>
            <li>Your public profile and contributions: everyone, or only signed-in members if you choose that in Account settings.</li>
            <li>Your CV and application: only you, the publisher of that opportunity and administrators.</li>
            <li>Expert-verification documents: only you and the administrators who review them.</li>
        </ul>

        <h2>How long we keep it</h2>
        <div class="table-responsive">
            <table class="table aq-table mb-3">
                <caption class="visually-hidden">Retention periods</caption>
                <thead><tr><th scope="col">Data</th><th scope="col">Kept</th></tr></thead>
                <tbody>
                    @foreach (LegalDocuments::RETENTION as $data => $period)
                        <tr><th scope="row" class="fw-normal">{{ $data }}</th><td data-label="Kept"><span>{{ $period }}</span></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h2>Your rights</h2>
        <p class="mb-0">
            Signed in, open <strong>Privacy &amp; my data</strong> from the account menu to download everything we hold about you
            as a file, or to delete your account. You can change email notifications and who sees your profile in Account settings.
        </p>
    </div>
</div>
@endsection

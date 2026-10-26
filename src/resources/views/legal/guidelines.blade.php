@extends('layouts.app')
@section('title', 'Community guidelines')
@section('subtitle', 'How we keep AQDesk useful and safe')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Community guidelines"
               subtitle="AQDesk works because members help each other in good faith. These rules apply to questions, answers, comments, articles and opportunities." />

<div class="card">
    <div class="card-body p-4 aq-legal">
        <h2>Be helpful</h2>
        <ul>
            <li>Ask clear, specific questions and say what you have already tried.</li>
            <li>Answer the question that was asked. If you are not sure, say so.</li>
            <li>Vote for content that is correct and useful, not for people you like.</li>
        </ul>

        <h2>Be respectful</h2>
        <ul>
            <li>No harassment, insults, hate speech or threats — towards anyone.</li>
            <li>Criticise ideas, not people. Assume beginners are doing their best.</li>
            <li>Never share someone else’s personal information (phone numbers, addresses, CVs).</li>
        </ul>

        <h2>Be honest</h2>
        <ul>
            <li>No spam, advertising or misleading opportunities. Opportunities must be real and open.</li>
            <li>Credit your sources. Do not copy other people’s work as your own.</li>
            <li>Experts: only claim qualifications you can show during verification.</li>
        </ul>

        <h2>When something breaks these rules</h2>
        <p>
            Use the <strong><i class="bi bi-flag" aria-hidden="true"></i> Report</strong> link next to the content. Moderators review every
            report. They either remove the content — the author is told which rule it broke — or keep it if it is within these
            guidelines. Every decision is recorded in the moderation log, and you are told the outcome of your report.
        </p>
        <p class="mb-0">
            Repeated or serious breaches can lead to an account being deactivated. See also the
            <a href="{{ route('terms') }}">terms of use</a> and the <a href="{{ route('privacy') }}">privacy policy</a>.
        </p>
    </div>
</div>
@endsection

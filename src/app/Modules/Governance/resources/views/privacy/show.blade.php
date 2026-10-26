@extends('layouts.app')
@section('title', 'Privacy & my data')
@section('subtitle', 'Your data rights')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Privacy & my data"
               subtitle="See what you agreed to, download everything AQDesk holds about you, or delete your account." />

<x-card title="Terms and privacy policy" icon="file-earmark-check" class="mb-4">
    @if ($accepted)
        <p class="aq-text-sm mb-0">
            <i class="bi bi-check-circle-fill aq-accent me-1" aria-hidden="true"></i>
            You accepted the current version ({{ \App\Modules\Governance\Support\LegalDocuments::VERSION }}) of the
            <a href="{{ route('terms') }}">terms of use</a> and the <a href="{{ route('privacy') }}">privacy policy</a>.
        </p>
    @else
        <p class="aq-text-sm">Please read and accept the current <a href="{{ route('terms') }}">terms of use</a> and <a href="{{ route('privacy') }}">privacy policy</a>.</p>
        <form method="POST" action="{{ route('privacy.accept') }}">
            @csrf
            <button type="submit" class="btn btn-accent btn-sm">I accept</button>
        </form>
    @endif
</x-card>

<x-card title="Download my data" icon="download" class="mb-4">
    <p class="aq-text-sm">A JSON file with everything AQDesk holds about you, in these sections:</p>
    <p class="aq-text-xs aq-muted">{{ collect($sections)->map(fn ($s) => str_replace('_', ' ', $s))->implode(' · ') }}</p>
    <a href="{{ route('privacy.export') }}" class="btn btn-quiet btn-sm"><i class="bi bi-download me-1" aria-hidden="true"></i> Download my data</a>
</x-card>

<x-card title="Delete my account" icon="person-x" class="aq-card-danger">
    <p class="aq-text-sm">This cannot be undone. When you delete your account:</p>
    <ul class="aq-text-sm">
        <li>your name, email, profile, photo, linked sign-ins and sessions are erased, and you can no longer sign in;</li>
        <li>your applications are withdrawn and your CVs and verification documents are deleted;</li>
        <li>your saved items, alerts, followed tags, notifications and learning progress are deleted;</li>
        <li>questions, answers, comments and articles you published stay, shown as written by “Deleted user”.</li>
    </ul>
    <form method="POST" action="{{ route('privacy.destroy') }}" class="row g-2 align-items-end" novalidate
          data-confirm-title="Delete your account?" data-confirm="Your personal data is erased and you are signed out. This cannot be undone."
          data-confirm-button="Delete my account">
        @csrf
        @method('DELETE')
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input @error('confirm') is-invalid @enderror" type="checkbox" id="confirm" name="confirm" value="1">
                <label class="form-check-label aq-text-sm" for="confirm">I understand that this cannot be undone</label>
                @error('confirm')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-sm">
            <label for="delete_password" class="form-label">Your password</label>
            <input type="password" id="delete_password" name="password" autocomplete="current-password"
                   class="form-control form-control-sm @error('password') is-invalid @enderror" @error('password') aria-describedby="delete_password_error" @enderror>
            @error('password')<div id="delete_password_error" class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-sm-auto">
            <button type="submit" class="btn btn-outline-danger btn-sm w-100">Delete my account</button>
        </div>
    </form>
</x-card>
@endsection

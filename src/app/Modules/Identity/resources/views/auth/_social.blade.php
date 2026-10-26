{{-- "Continue with Google / GitHub" — only for providers with OAuth keys. --}}
@php $providers = app(\App\Modules\Identity\Services\SocialLoginService::class)->enabled(); @endphp

@if ($providers)
    <div class="aq-social">
        @foreach ($providers as $key => $label)
            <a href="{{ route('social.redirect', $key) }}" class="btn btn-quiet aq-social-btn">
                <i class="bi bi-{{ $key }}" aria-hidden="true"></i> Continue with {{ $label }}
            </a>
        @endforeach
    </div>
    <div class="aq-or" role="separator"><span>or {{ $verb }} with email</span></div>
@endif

<?php

namespace App\Modules\Governance\Listeners;

use App\Modules\Governance\Services\PrivacyService;
use Illuminate\Auth\Events\Registered;

/**
 * Registration requires ticking "I agree to the terms and privacy policy"
 * (Identity's RegisterRequest); this records which version was accepted.
 */
class RecordConsentOnRegistration
{
    public function __construct(private PrivacyService $privacy) {}

    public function handle(Registered $event): void
    {
        $this->privacy->accept($event->user);
    }
}

<?php

namespace App\Modules\Governance;

use App\Modules\Governance\Listeners\RecordConsentOnRegistration;
use App\Modules\Governance\Models\Consent;
use App\Modules\Governance\Models\ModerationAction;
use App\Modules\Governance\Models\Report;
use App\Modules\Identity\Models\User;
use App\Modules\ModuleServiceProvider;
use Illuminate\Auth\Events\Registered;

/**
 * Digital governance: content reports, the moderation queue and decision
 * log (roadmap 2.5), consent to the terms and privacy policy, and the data
 * rights "download my data" and "delete my account" (2.6). The activity log
 * (audit trail) is a Platform service.
 */
class GovernanceServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Governance';

    protected function listeners(): array
    {
        return [Registered::class => RecordConsentOnRegistration::class.'@handle'];
    }

    protected function morphMap(): array
    {
        return [
            'report'            => Report::class,
            'moderation_action' => ModerationAction::class,
        ];
    }

    protected function personalData(): array
    {
        return [
            'reports_you_filed' => fn (User $user) => Report::where('user_id', $user->id)->get()
                ->map(fn (Report $r) => ['about' => $r->reportable_type.' #'.$r->reportable_id, 'reason' => $r->reasonLabel(),
                    'details' => $r->details, 'status' => $r->status, 'filed_at' => $r->created_at->toIso8601String()])->all(),
            'consents' => fn (User $user) => Consent::where('user_id', $user->id)->get(['version', 'accepted_at'])->toArray(),
        ];
    }

    protected function personalDataErasers(): array
    {
        return [
            // Reports stay for the moderation record, without saying who filed them.
            fn (User $user) => Report::where('user_id', $user->id)->update(['user_id' => null]),
            fn (User $user) => Consent::where('user_id', $user->id)->delete(),
        ];
    }
}

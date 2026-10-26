<?php

namespace App\Modules\Knowledge\Notifications;

use App\Modules\Knowledge\Models\ExpertVerification;
use App\Modules\Platform\Notifications\AppNotification;

/** Tells reviewers that someone asked to be verified as an expert. */
class NewVerificationRequest extends AppNotification
{
    public function __construct(private ExpertVerification $request) {}

    protected function title(): string
    {
        return 'Expert verification request';
    }

    protected function body(): string
    {
        return $this->request->user->name.' asked to be verified in “'.$this->request->field.'”.';
    }

    protected function url(): string
    {
        return route('admin.verifications.show', $this->request);
    }

    protected function icon(): string
    {
        return 'patch-question';
    }
}

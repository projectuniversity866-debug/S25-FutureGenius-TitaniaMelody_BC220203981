<?php

namespace App\Modules\Knowledge\Notifications;

use App\Modules\Knowledge\Models\ExpertVerification;
use App\Modules\Platform\Notifications\AppNotification;
use Illuminate\Support\Str;

/** Tells the applicant whether they were verified, with the reviewer's note. */
class VerificationDecided extends AppNotification
{
    public function __construct(private ExpertVerification $request) {}

    protected function title(): string
    {
        return $this->request->status === 'approved' ? 'You are now a verified expert' : 'Expert verification not approved';
    }

    protected function body(): string
    {
        $body = $this->request->status === 'approved'
            ? 'Your profile now shows the verified badge for “'.$this->request->field.'”, and you can write for the Knowledge Hub.'
            : 'Your request for “'.$this->request->field.'” was not approved.';

        return $this->request->review_note ? $body.' Note: “'.Str::limit($this->request->review_note, 120).'”' : $body;
    }

    protected function url(): string
    {
        return route('verification.show');
    }

    protected function icon(): string
    {
        return $this->request->status === 'approved' ? 'patch-check' : 'patch-exclamation';
    }
}

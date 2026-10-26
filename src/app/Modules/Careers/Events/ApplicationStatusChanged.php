<?php

namespace App\Modules\Careers\Events;

use App\Modules\Careers\Models\JobApplication;
use Illuminate\Foundation\Events\Dispatchable;

class ApplicationStatusChanged
{
    use Dispatchable;

    /** $message is the reviewer's optional note to the applicant. */
    public function __construct(public JobApplication $application, public string $from, public ?string $message = null) {}
}

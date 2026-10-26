<?php

namespace App\Modules\Careers\Events;

use App\Modules\Careers\Models\JobPost;
use Illuminate\Foundation\Events\Dispatchable;

class OpportunityPublished
{
    use Dispatchable;

    public function __construct(public JobPost $job) {}
}

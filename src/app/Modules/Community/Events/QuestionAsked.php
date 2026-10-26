<?php

namespace App\Modules\Community\Events;

use App\Modules\Community\Models\Question;
use Illuminate\Foundation\Events\Dispatchable;

class QuestionAsked
{
    use Dispatchable;

    public function __construct(public Question $question) {}
}

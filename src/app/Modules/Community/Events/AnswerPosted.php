<?php

namespace App\Modules\Community\Events;

use App\Modules\Community\Models\Answer;
use Illuminate\Foundation\Events\Dispatchable;

class AnswerPosted
{
    use Dispatchable;

    public function __construct(public Answer $answer) {}
}

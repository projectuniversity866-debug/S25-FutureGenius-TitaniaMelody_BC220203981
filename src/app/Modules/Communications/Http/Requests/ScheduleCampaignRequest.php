<?php

namespace App\Modules\Communications\Http\Requests;

use App\Modules\Communications\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Choosing when a draft goes out, typed in a chosen time zone and stored in UTC. */
class ScheduleCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('campaign'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'send_at'  => ['required', 'date'],
            'timezone' => ['required', Rule::in(array_keys(Campaign::TIMEZONES))],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $validator->errors()->any() && ! $this->sendAt()->isFuture()) {
                $validator->errors()->add('send_at', 'Choose a time in the future, or use Send now.');
            }
        }];
    }

    public function sendAt(): Carbon
    {
        return Carbon::parse($this->input('send_at'), $this->input('timezone'))->utc();
    }
}

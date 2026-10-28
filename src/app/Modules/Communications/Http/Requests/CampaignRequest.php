<?php

namespace App\Modules\Communications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Writing a campaign, or editing a draft. */
class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        return $campaign ? $this->user()->can('update', $campaign) : $this->user()->can('newsletter.send');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:5', 'max:150'],
            'body'    => ['required', 'string', 'min:20', 'max:50000'],
        ];
    }
}

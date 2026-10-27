<?php

namespace App\Modules\Communications\Http\Requests;

use App\Modules\Communications\Models\Announcement;
use App\Modules\Communications\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** Posting or editing a site announcement; times are typed in a chosen zone and stored in UTC. */
class AnnouncementRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'min:5', 'max:120'],
            'body'           => ['required', 'string', 'min:10', 'max:500'],
            'link_url'       => ['nullable', 'url:http,https', 'max:500'],
            'link_label'     => ['nullable', 'required_with:link_url', 'string', 'max:60'],
            'tone'           => ['required', Rule::in(array_keys(Announcement::TONES))],
            'audience'       => ['required', Rule::in(array_keys(Announcement::AUDIENCES))],
            'is_dismissible' => ['boolean'],
            'timezone'       => ['required', Rule::in(array_keys(Campaign::TIMEZONES))],
            'starts_at'      => ['required', 'date'],
            'ends_at'        => ['nullable', 'date', 'after:starts_at'],
        ];
    }

    /** @return array<string, mixed> */
    public function announcementData(): array
    {
        $data = $this->validated() + ['link_url' => null, 'link_label' => null];   // left out = removed
        $data['is_dismissible'] = $this->boolean('is_dismissible');
        foreach (['starts_at', 'ends_at'] as $key) {
            $data[$key] = filled($data[$key] ?? null) ? Carbon::parse($data[$key], $data['timezone'])->utc() : null;
        }

        return $data;
    }
}

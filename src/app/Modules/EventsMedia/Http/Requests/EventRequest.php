<?php

namespace App\Modules\EventsMedia\Http\Requests;

use App\Modules\EventsMedia\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Creating or editing an event. Times are typed in the event's own time
 * zone and stored in UTC (see eventData()).
 */
class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event ? $this->user()->can('update', $event) : $this->user()->can('create', Event::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // Registration questions: one of the organizer's own forms.
        $organizer = $this->route('event')?->user_id ?? $this->user()->id;

        return [
            'title'                  => ['required', 'string', 'min:5', 'max:150'],
            'summary'                => ['required', 'string', 'min:10', 'max:255'],
            'description'            => ['nullable', 'string', 'max:10000'],
            'format'                 => ['required', Rule::in(array_keys(Event::FORMATS))],
            'venue'                  => ['nullable', 'required_unless:format,online', 'string', 'max:255'],
            'meeting_url'            => ['nullable', 'url:https', 'max:500'],
            'meeting_notes'          => ['nullable', 'string', 'max:255'],
            'create_jitsi'           => ['nullable', 'boolean'],
            'timezone'               => ['required', Rule::in(array_keys(Event::TIMEZONES))],
            'starts_at'              => ['required', 'date'],
            'ends_at'                => ['required', 'date', 'after:starts_at'],
            'capacity'               => ['nullable', 'integer', 'min:1', 'max:100000'],
            'registration_closes_at' => ['nullable', 'date', 'before_or_equal:starts_at'],
            'form_id'                => ['nullable', 'integer', Rule::exists('forms', 'id')->where('user_id', $organizer)],
        ];
    }

    /** A new or moved event must start in the future. */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->has('starts_at') || $validator->errors()->has('timezone')) {
                return;
            }
            $start = $this->utc('starts_at');
            $unchanged = $this->route('event')?->starts_at->equalTo($start);
            if (! $unchanged && $start->isPast()) {
                $validator->errors()->add('starts_at', 'The start must be in the future.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'venue.required_unless'                  => 'Say where it takes place (for an online event, leave this empty).',
            'ends_at.after'                          => 'The end must be after the start.',
            'registration_closes_at.before_or_equal' => 'Registration must close before the event starts.',
            'form_id.exists'                         => 'Choose one of your own forms.',
            'meeting_url.url'                        => 'Paste the full https:// joining link from Zoom, Google Meet, Jitsi or Teams.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'starts_at' => 'start', 'ends_at' => 'end', 'registration_closes_at' => 'registration deadline', 'form_id' => 'registration form',
            'meeting_url' => 'joining link', 'meeting_notes' => 'joining notes',
        ];
    }

    /** Validated data with times converted to UTC. @return array<string, mixed> */
    public function eventData(): array
    {
        $data = $this->validated();
        foreach (['starts_at', 'ends_at', 'registration_closes_at'] as $key) {
            $data[$key] = filled($data[$key] ?? null) ? $this->utc($key) : null;
        }
        if ($data['format'] === 'online') {
            $data['venue'] = null;
        }
        if ($data['format'] === 'in_person') {
            $data['meeting_url'] = $data['meeting_notes'] = null;
        } elseif (! empty($data['create_jitsi']) && blank($data['meeting_url'] ?? null)) {
            $data['meeting_url'] = Event::jitsiRoom($data['title']);   // roadmap 3.8: a room without any account
        }
        unset($data['create_jitsi']);

        return $data;
    }

    private function utc(string $key): Carbon
    {
        return Carbon::parse($this->input($key), $this->input('timezone'))->utc();
    }
}

<?php

namespace App\Modules\EventsMedia\Http\Requests;

use App\Modules\EventsMedia\Models\PodcastSeries;
use Illuminate\Foundation\Http\FormRequest;

/** Starting or editing a podcast series. */
class PodcastSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $series = $this->route('series');

        return $series ? $this->user()->can('update', $series) : $this->user()->can('create', PodcastSeries::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:3', 'max:150'],
            'summary'     => ['required', 'string', 'min:10', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

<?php

namespace App\Modules\Platform\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Platform\Models\Form;
use App\Modules\Platform\Models\FormField;
use App\Modules\Platform\Models\FormResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Respondents submitting forms, and creators reading the results (roadmap 2.4). */
class FormSubmissionService
{
    /** Whether this person may submit now; null means yes, otherwise the reason. */
    public function refusal(Form $form, ?User $user): ?string
    {
        if (! $form->isAcceptingResponses()) {
            return 'This form is not accepting responses.';
        }
        if (($form->requires_login || $form->one_response_per_user) && ! $user) {
            return 'Sign in to fill in this form.';
        }
        if ($form->one_response_per_user && $form->responses()->where('user_id', $user->id)->exists()) {
            return 'You have already responded to this form.';
        }

        return null;
    }

    /**
     * Validate the answers against the form's own fields and store them.
     *
     * @param  array<string, mixed>  $input  the request's "answers" array, keyed by field id
     *
     * @throws ValidationException
     */
    public function submit(Form $form, ?User $user, array $input): FormResponse
    {
        if ($reason = $this->refusal($form, $user)) {
            throw ValidationException::withMessages(['form' => $reason]);
        }

        $fields = $form->fields;
        $rules = [];
        $names = [];
        foreach ($fields as $field) {
            $rules["answers.{$field->id}"] = $field->rules();
            if ($field->type === 'multiple_choice') {
                $rules["answers.{$field->id}.*"] = [Rule::in($field->options ?? [])];
            }
            $names["answers.{$field->id}"] = '“'.$field->label.'”';
            $names["answers.{$field->id}.*"] = '“'.$field->label.'”';
        }
        $valid = Validator::make(['answers' => $input], $rules, [], $names)->validate()['answers'] ?? [];

        return DB::transaction(function () use ($form, $user, $fields, $valid) {
            $response = $form->responses()->create(['user_id' => $user?->id]);

            foreach ($fields as $field) {
                $value = $valid[$field->id] ?? null;
                if ($value === null || $value === '' || $value === []) {
                    continue;
                }
                $response->answers()->create([
                    'form_field_id' => $field->id,
                    'value'         => is_array($value) ? json_encode(array_values($value)) : ($field->type === 'consent' ? 'Yes' : (string) $value),
                ]);
            }

            return $response;
        });
    }

    /**
     * Per-field summary for the results page: counts per option for choice
     * fields, average for ratings, the latest answers for text.
     *
     * @return Collection<int, array{field: FormField, answered: int, counts?: array<string, int>, average?: ?float, latest?: Collection}>
     */
    public function summary(Form $form): Collection
    {
        return $form->fields()->with('answers')->get()->map(function (FormField $field) {
            $values = $field->answers->map(fn ($a) => json_decode((string) $a->value, true) ?? $a->value);
            $row = ['field' => $field, 'answered' => $values->count()];

            if ($field->hasOptions() || $field->type === 'rating' || $field->type === 'consent') {
                $keys = $field->hasOptions() ? $field->options : ($field->type === 'rating' ? ['1', '2', '3', '4', '5'] : ['Yes']);
                $counts = array_fill_keys($keys, 0);
                foreach ($values->flatten() as $v) {
                    if (array_key_exists((string) $v, $counts)) {
                        $counts[(string) $v]++;
                    }
                }
                $row['counts'] = $counts;
            }
            if ($field->type === 'rating') {
                $row['average'] = $values->count() ? round($values->avg(), 1) : null;
            }
            if (! isset($row['counts'])) {
                $row['latest'] = $field->answers->sortByDesc('id')->take(5)->map->display()->values();
            }

            return $row;
        });
    }

    /** Every response as CSV, one column per field. */
    public function export(Form $form): StreamedResponse
    {
        $fields = $form->fields;
        $responses = $form->responses()->with('answers', 'user')->oldest()->get();

        return response()->streamDownload(function () use ($fields, $responses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Submitted at', 'Respondent'], $fields->pluck('label')->all()));
            foreach ($responses as $response) {
                $byField = $response->answers->keyBy('form_field_id');
                $row = [$response->created_at->toDateTimeString(), $response->user?->email ?? 'Anonymous'];
                foreach ($fields as $field) {
                    $row[] = $byField->has($field->id) ? $byField[$field->id]->display() : '';
                }
                // Spreadsheet formula injection: never start a cell with = + - @.
                fputcsv($out, array_map(fn ($cell) => preg_match('/^[=+\-@]/', (string) $cell) ? "'".$cell : $cell, $row));
            }
            fclose($out);
        }, 'responses-'.$form->slug.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

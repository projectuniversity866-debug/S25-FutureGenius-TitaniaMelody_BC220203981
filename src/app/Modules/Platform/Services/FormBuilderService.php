<?php

namespace App\Modules\Platform\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Platform\Models\Form;
use App\Modules\Platform\Models\FormField;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Building forms (roadmap 2.4): the form's settings, its fields and its status. */
class FormBuilderService
{
    /** @param  array<string, mixed>  $data  validated FormSettingsRequest data */
    public function create(User $creator, array $data): Form
    {
        return Form::create($data + ['user_id' => $creator->id, 'slug' => Form::makeSlug($data['title']), 'status' => 'draft']);
    }

    /** @param  array<string, mixed>  $data  validated FormSettingsRequest data */
    public function update(Form $form, array $data): Form
    {
        $form->update($data);

        return $form;
    }

    /** @throws ValidationException when opening a form that has no fields */
    public function setStatus(Form $form, string $status): Form
    {
        if ($status === 'open' && $form->fields()->doesntExist()) {
            throw ValidationException::withMessages(['status' => 'Add at least one field before opening the form.']);
        }

        $form->update(['status' => $status]);

        return $form;
    }

    public function delete(Form $form): void
    {
        $form->delete();
    }

    /** @param  array<string, mixed>  $data  validated FormFieldRequest data */
    public function addField(Form $form, array $data): FormField
    {
        return $form->fields()->create($this->fieldAttributes($data) + [
            'position' => (int) $form->fields()->max('position') + 1,
        ]);
    }

    /** @param  array<string, mixed>  $data  validated FormFieldRequest data */
    public function updateField(FormField $field, array $data): FormField
    {
        $field->update($this->fieldAttributes($data));

        return $field;
    }

    /** Swap a field with its neighbour ("up" or "down"); nothing happens at either end. */
    public function moveField(FormField $field, string $direction): void
    {
        $neighbour = FormField::where('form_id', $field->form_id)
            ->where('position', $direction === 'up' ? '<' : '>', $field->position)
            ->orderBy('position', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if (! $neighbour) {
            return;
        }

        DB::transaction(function () use ($field, $neighbour) {
            [$a, $b] = [$field->position, $neighbour->position];
            $field->update(['position' => $b]);
            $neighbour->update(['position' => $a]);
        });
    }

    /** Deleting a field also deletes everyone's answers to it. */
    public function deleteField(FormField $field): void
    {
        DB::transaction(function () use ($field) {
            FormField::where('form_id', $field->form_id)->where('position', '>', $field->position)->decrement('position');
            $field->delete();
        });
    }

    /** "One choice per line" → a clean list; non-choice fields keep no options. */
    private function fieldAttributes(array $data): array
    {
        $options = null;
        if (in_array($data['type'], FormField::CHOICE_TYPES, true)) {
            $options = collect(preg_split('/\R/', (string) ($data['options'] ?? '')))
                ->map(fn ($o) => trim($o))->filter()->unique()->take(FormField::MAX_OPTIONS)->values()->all();
        }

        return [
            'type'        => $data['type'],
            'label'       => $data['label'],
            'help'        => $data['help'] ?? null,
            'options'     => $options,
            'is_required' => (bool) ($data['is_required'] ?? false),
        ];
    }
}

<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Platform\Models\Form;
use App\Modules\Platform\Models\FormResponse;

/**
 * Suite B — form builder (2.4): creators build forms, respondents submit,
 * creators read the results. Cases B1–B9.
 */
class FormBuilderTest extends AqDeskTestCase
{
    /** A creator's form with a required short answer, a choice and a rating. */
    private function buildForm(array $settings = []): Form
    {
        $expert = $this->expert();
        $this->actingAs($expert)->post(route('forms.store'), array_merge(['title' => 'Event registration'], $settings))->assertSessionHasNoErrors();
        $form = Form::where('title', 'Event registration')->latest('id')->firstOrFail();

        foreach ([
            ['type' => 'short_text', 'label' => 'Your name', 'is_required' => '1'],
            ['type' => 'single_choice', 'label' => 'Which day?', 'options' => "Saturday\nSunday\nSunday", 'is_required' => '1'],
            ['type' => 'multiple_choice', 'label' => 'Meals', 'options' => "Lunch\nDinner"],
            ['type' => 'rating', 'label' => 'How excited are you?'],
        ] as $field) {
            $this->actingAs($expert)->post(route('forms.fields.store', $form), $field)->assertSessionHasNoErrors();
        }

        return $form->fresh('fields');
    }

    private function answers(Form $form, array $values): array
    {
        return ['answers' => collect($values)->mapWithKeys(fn ($v, $i) => [$form->fields[$i]->id => $v])->all()];
    }

    /** B1 — creators build a form; options are de-duplicated; members without the permission cannot. */
    public function test_b1_build_a_form(): void
    {
        $form = $this->buildForm();

        $this->assertSame(['Saturday', 'Sunday'], $form->fields[1]->options);
        $this->assertSame('draft', $form->status);
        $this->actingAs($this->member())->get(route('forms.create'))->assertForbidden();
        $this->actingAs($this->otherExpert())->get(route('forms.edit', $form))->assertForbidden();
        $this->actingAs($this->expert())->post(route('forms.fields.store', $form), ['type' => 'dropdown', 'label' => 'Pick one'])
            ->assertSessionHasErrors('options');
    }

    /** B2 — a draft is a private preview; an empty form cannot be opened. */
    public function test_b2_draft_and_open(): void
    {
        $this->actingAs($this->expert())->post(route('forms.store'), ['title' => 'Empty form']);
        $empty = Form::where('title', 'Empty form')->firstOrFail();
        $this->actingAs($this->expert())->put(route('forms.status', $empty), ['status' => 'open'])->assertSessionHasErrors('status');

        $form = $this->buildForm();
        $this->actingAs($this->expert())->get(route('forms.fill', $form))->assertOk()->assertSee('Preview');
        $this->signOut();
        $this->get(route('forms.fill', $form))->assertNotFound();

        $this->actingAs($this->expert())->put(route('forms.status', $form), ['status' => 'open']);
        $this->signOut();
        $this->get(route('forms.fill', $form))->assertOk()->assertSee('Which day?')->assertSee('Saturday');
    }

    /** B3 — a visitor submits valid answers; they are stored as given. */
    public function test_b3_submit(): void
    {
        $form = $this->buildForm();
        $form->update(['status' => 'open']);
        $this->signOut();

        $this->post(route('forms.submit', $form), $this->answers($form, ['Ayesha Khan', 'Sunday', ['Lunch', 'Dinner'], 5]))
            ->assertRedirect(route('forms.thanks', $form));

        $response = FormResponse::where('form_id', $form->id)->with('answers')->firstOrFail();
        $this->assertNull($response->user_id);
        $this->assertSame(['Ayesha Khan', 'Sunday', 'Lunch; Dinner', '5'], $response->answers->sortBy('form_field_id')->map->display()->values()->all());
        $this->get(route('forms.thanks', $form))->assertSee('Response received');
    }

    /** B4 — answers are validated against the form's own fields. */
    public function test_b4_validation(): void
    {
        $form = $this->buildForm();
        $form->update(['status' => 'open']);
        $this->signOut();

        $this->post(route('forms.submit', $form), $this->answers($form, ['', 'Monday', ['Breakfast'], 9]))
            ->assertSessionHasErrors([
                'answers.'.$form->fields[0]->id, 'answers.'.$form->fields[1]->id,
                'answers.'.$form->fields[2]->id.'.0', 'answers.'.$form->fields[3]->id,
            ]);
        $this->assertSame(0, $form->responses()->count());
    }

    /** B5 — sign-in and one-response rules are enforced. */
    public function test_b5_response_rules(): void
    {
        $form = $this->buildForm(['one_response_per_user' => '1']);
        $form->update(['status' => 'open']);
        $this->signOut();

        $this->get(route('forms.fill', $form))->assertSee('Sign in to fill in this form.');
        $this->post(route('forms.submit', $form), $this->answers($form, ['Guest', 'Sunday']))->assertSessionHasErrors('form');

        $member = $this->member();
        $this->actingAs($member)->post(route('forms.submit', $form), $this->answers($form, ['Usman', 'Saturday']))->assertSessionHasNoErrors();
        $this->actingAs($member)->post(route('forms.submit', $form), $this->answers($form, ['Usman again', 'Sunday']))
            ->assertSessionHasErrors(['form' => 'You have already responded to this form.']);
        $this->assertSame(1, $form->responses()->count());
    }

    /** B6 — closed forms, and forms past their closing time, refuse responses. */
    public function test_b6_closing(): void
    {
        $form = $this->buildForm();
        $form->update(['status' => 'closed']);
        $this->signOut();
        $this->post(route('forms.submit', $form), $this->answers($form, ['Late', 'Sunday']))->assertSessionHasErrors('form');

        $form->update(['status' => 'open', 'closes_at' => now()->subMinute()]);
        $this->get(route('forms.fill', $form))->assertSee('This form is not accepting responses.');
    }

    /** B7 — results: summary per question, the individual table, and a safe CSV. */
    public function test_b7_results_and_export(): void
    {
        $form = $this->buildForm();
        $form->update(['status' => 'open']);
        $this->signOut();
        $this->post(route('forms.submit', $form), $this->answers($form, ['=HYPERLINK("x")', 'Sunday', ['Lunch'], 4]));
        $this->post(route('forms.submit', $form), $this->answers($form, ['Bilal', 'Sunday', [], 2]));

        $this->actingAs($this->expert())->get(route('forms.responses', $form))->assertOk()
            ->assertSee('2 responses')->assertSee('Average')->assertSee('3')->assertSee('Bilal');

        $csv = $this->actingAs($this->expert())->get(route('forms.export', $form))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Your name"', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);   // formula neutralised

        $this->actingAs($this->member())->get(route('forms.responses', $form))->assertForbidden();
        $this->actingAs($this->member())->get(route('forms.export', $form))->assertForbidden();
    }

    /** B8 — fields can be reordered, edited and deleted (with their answers). */
    public function test_b8_edit_fields(): void
    {
        $form = $this->buildForm();
        [$name, $day] = [$form->fields[0], $form->fields[1]];

        $this->actingAs($this->expert())->put(route('forms.fields.move', $day), ['direction' => 'up']);
        $this->assertSame([$day->id, $name->id], $form->fields()->take(2)->pluck('id')->map('intval')->all());

        $this->actingAs($this->expert())->put(route('forms.fields.update', $name), ['type' => 'email', 'label' => 'Your email', 'is_required' => '1']);
        $this->assertSame(['email', 'Your email'], [$name->fresh()->type, $name->fresh()->label]);

        $this->actingAs($this->expert())->delete(route('forms.fields.destroy', $day));
        $this->assertSame(3, $form->fields()->count());
        $this->assertSame([1, 2, 3], $form->fields()->pluck('position')->map('intval')->all());
    }

    /** B9 — the seeded survey is open and has responses with a summary. */
    public function test_b9_seeded_survey(): void
    {
        $form = Form::where('title', 'Accessibility workshop feedback')->firstOrFail();
        $this->get(route('forms.fill', $form))->assertOk()->assertSee('How useful was the workshop?');
        $this->actingAs($this->expert())->get(route('forms.index'))->assertSee('Accessibility workshop feedback');
        $this->actingAs($this->expert())->get(route('forms.responses', $form))->assertOk()->assertSee('3 responses')->assertSee('Screen reader demo');
    }
}

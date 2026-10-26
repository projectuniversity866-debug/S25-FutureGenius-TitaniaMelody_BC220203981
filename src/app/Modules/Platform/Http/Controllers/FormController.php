<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Platform\Http\Requests\FormFieldRequest;
use App\Modules\Platform\Http\Requests\FormSettingsRequest;
use App\Modules\Platform\Models\Form;
use App\Modules\Platform\Models\FormField;
use App\Modules\Platform\Services\FormBuilderService;
use App\Modules\Platform\Services\FormSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The form creator's side of the forms engine (roadmap 2.4). */
class FormController extends Controller
{
    public function __construct(private FormBuilderService $builder, private FormSubmissionService $submissions) {}

    public function index(Request $request): View
    {
        $forms = Form::where('user_id', $request->user()->id)->withCount(['fields', 'responses'])->latest()->get();

        return view('platform::forms.index', compact('forms'));
    }

    public function create(): View
    {
        $this->authorize('create', Form::class);

        return view('platform::forms.create');
    }

    public function store(FormSettingsRequest $request): RedirectResponse
    {
        $form = $this->builder->create($request->user(), $request->validated());

        return redirect()->route('forms.edit', $form)->with('status', 'Form created as a draft. Add its fields, then open it.');
    }

    /** The builder: settings, fields and status on one page. */
    public function edit(Form $form): View
    {
        $this->authorize('update', $form);

        $form->load('fields')->loadCount('responses');

        return view('platform::forms.edit', compact('form'));
    }

    public function update(FormSettingsRequest $request, Form $form): RedirectResponse
    {
        $this->builder->update($form, $request->validated());

        return redirect()->route('forms.edit', $form)->with('status', 'Settings saved.');
    }

    public function status(Request $request, Form $form): RedirectResponse
    {
        $this->authorize('update', $form);

        $status = in_array($request->input('status'), Form::STATUSES, true) ? $request->input('status') : $form->status;
        $this->builder->setStatus($form, $status);

        return redirect()->route('forms.edit', $form)->with('status', match ($status) {
            'open'   => 'The form is open. Share its link to collect responses.',
            'closed' => 'The form is closed to new responses.',
            default  => 'The form is back in draft.',
        });
    }

    public function destroy(Form $form): RedirectResponse
    {
        $this->authorize('delete', $form);

        $this->builder->delete($form);

        return redirect()->route('forms.index')->with('status', 'Form and its responses deleted.');
    }

    // ------------------------------------------------------------- fields

    public function storeField(FormFieldRequest $request, Form $form): RedirectResponse
    {
        $field = $this->builder->addField($form, $request->validated());

        return redirect()->to(route('forms.edit', $form).'#field-'.$field->id)->with('status', 'Field added.');
    }

    public function updateField(FormFieldRequest $request, FormField $field): RedirectResponse
    {
        $this->builder->updateField($field, $request->validated());

        return redirect()->to(route('forms.edit', $field->form).'#field-'.$field->id)->with('status', 'Field saved.');
    }

    public function moveField(Request $request, FormField $field): RedirectResponse
    {
        $this->authorize('update', $field->form);

        $this->builder->moveField($field, $request->input('direction') === 'up' ? 'up' : 'down');

        return redirect()->to(route('forms.edit', $field->form).'#field-'.$field->id);
    }

    public function destroyField(FormField $field): RedirectResponse
    {
        $this->authorize('update', $field->form);

        $form = $field->form;
        $this->builder->deleteField($field);

        return redirect()->to(route('forms.edit', $form).'#fields')->with('status', 'Field deleted.');
    }

    // ---------------------------------------------------------- responses

    public function responses(Form $form): View
    {
        $this->authorize('viewResponses', $form);

        $form->load('fields');
        $summary = $this->submissions->summary($form);
        $responses = $form->responses()->with('answers', 'user')->latest()->paginate(20);

        return view('platform::forms.responses', compact('form', 'summary', 'responses'));
    }

    public function export(Form $form): StreamedResponse
    {
        $this->authorize('viewResponses', $form);

        return $this->submissions->export($form);
    }
}

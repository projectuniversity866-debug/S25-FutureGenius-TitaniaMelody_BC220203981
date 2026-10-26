<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Platform\Models\Form;
use App\Modules\Platform\Services\FormSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The respondent's side: fill in a form by its public link (roadmap 2.4). */
class PublicFormController extends Controller
{
    public function __construct(private FormSubmissionService $submissions) {}

    public function show(Request $request, Form $form): View
    {
        $this->authorize('fill', $form);

        $form->load('fields');
        $refusal = $this->submissions->refusal($form, $request->user());

        return view('platform::forms.fill', compact('form', 'refusal'));
    }

    public function submit(Request $request, Form $form): RedirectResponse
    {
        $this->authorize('fill', $form);

        $answers = $request->input('answers', []);
        $this->submissions->submit($form->load('fields'), $request->user(), is_array($answers) ? $answers : []);

        return redirect()->route('forms.thanks', $form);
    }

    public function thanks(Form $form): View
    {
        $this->authorize('fill', $form);

        return view('platform::forms.thanks', compact('form'));
    }
}

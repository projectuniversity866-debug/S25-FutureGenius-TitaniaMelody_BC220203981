<?php

namespace App\Modules\Platform\Database\Seeders;

use App\Modules\Identity\Models\User;
use App\Modules\Platform\Models\Form;
use Illuminate\Database\Seeder;

/** Demo form (roadmap 2.4): an open feedback survey with a few responses. */
class FormSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id', 'email');

        if (Form::where('title', 'Accessibility workshop feedback')->exists()) {
            return;
        }

        $form = Form::create([
            'user_id'              => $users['expert@aqdesk.test'],
            'title'                => 'Accessibility workshop feedback',
            'slug'                 => Form::makeSlug('Accessibility workshop feedback'),
            'description'          => 'Two minutes to tell us what worked and what to change for the next session.',
            'status'               => 'open',
            'confirmation_message' => 'Thank you — your feedback shapes the next workshop.',
        ]);

        $fields = [
            ['rating', 'How useful was the workshop?', null, null, true],
            ['single_choice', 'Which part helped you most?', null, ['Screen reader demo', 'Colour contrast', 'Keyboard testing', 'Q&A'], true],
            ['multiple_choice', 'Which topics should we cover next?', 'Choose any.', ['ARIA patterns', 'Accessible forms', 'Testing tools', 'Writing alt text'], false],
            ['long_text', 'Anything else?', null, null, false],
            ['consent', 'You may contact me about future workshops', null, null, false],
        ];
        foreach ($fields as $position => [$type, $label, $help, $options, $required]) {
            $form->fields()->create([
                'type' => $type, 'label' => $label, 'help' => $help, 'options' => $options,
                'is_required' => $required, 'position' => $position + 1,
            ]);
        }

        $fields = $form->fields()->get()->values();
        $answers = [
            ['member@aqdesk.test', 5, 'Screen reader demo', ['ARIA patterns', 'Accessible forms'], 'Seeing NVDA read my own page was eye-opening.', true],
            ['sara@aqdesk.test', 4, 'Keyboard testing', ['Testing tools'], null, false],
            [null, 4, 'Colour contrast', ['Accessible forms', 'Writing alt text'], 'More time for questions, please.', true],
        ];
        foreach ($answers as [$email, $rating, $part, $topics, $comment, $consent]) {
            $response = $form->responses()->create(['user_id' => $email ? $users[$email] : null]);
            foreach ([$rating, $part, json_encode($topics), $comment, $consent ? 'Yes' : null] as $i => $value) {
                if ($value !== null) {
                    $response->answers()->create(['form_field_id' => $fields[$i]->id, 'value' => (string) $value]);
                }
            }
        }
    }
}

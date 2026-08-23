<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;

/**
 * Realistic Q&A content so the prototype demo is not an empty shell.
 */
class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');
        $users      = User::pluck('id', 'email');

        $threads = [
            [
                'category' => 'web-development',
                'author'   => 'member@aqdesk.test',
                'title'    => 'What is the cleanest way to implement role-based access control in Laravel?',
                'body'     => "I am building a community platform where users can be an administrator, an expert or an ordinary member.\n\nRight now I am checking the role inside every controller method, which is getting repetitive. Should I use middleware, gates, policies, or a package? I would like to understand the trade-offs rather than just copy a package.",
                'tags'     => ['laravel', 'authorization', 'middleware'],
                'answers'  => [
                    [
                        'author' => 'expert@aqdesk.test',
                        'accept' => true,
                        'votes'  => 4,
                        'body'   => "Start with route middleware for coarse checks and policies for per-record checks.\n\nMiddleware answers \"may this role reach this area at all\" — e.g. only admins reach /admin. Policies answer \"may this specific user act on this specific record\" — e.g. only the author or an admin may edit a question.\n\nA package is worth it once you need permissions that are assigned at runtime rather than fixed roles. For a fixed set of three roles, the framework alone is cleaner and you keep full understanding of your own code.",
                    ],
                    [
                        'author' => 'faisal@aqdesk.test',
                        'accept' => false,
                        'votes'  => 1,
                        'body'   => "One small addition: put a hasRole() helper on the User model so your Blade templates read well. Something like @if(auth()->user()->isAdmin()) is far easier to scan than comparing IDs in the view.",
                    ],
                ],
            ],
            [
                'category' => 'artificial-intelligence',
                'author'   => 'sara@aqdesk.test',
                'title'    => 'How much labelled data do I realistically need to fine-tune a small language model?',
                'body'     => "I am working on a final year project that classifies student support tickets into categories.\n\nEverything I read either uses a handful of examples or hundreds of thousands. For a narrow classification task with around eight categories, what is a realistic starting point, and how do I know when more data stops helping?",
                'tags'     => ['machine-learning', 'nlp', 'fine-tuning'],
                'answers'  => [
                    [
                        'author' => 'expert@aqdesk.test',
                        'accept' => false,
                        'votes'  => 3,
                        'body'   => "For eight well-separated categories, a few hundred clean examples per class is a sensible starting point, and quality matters far more than volume.\n\nPlot a learning curve: train on 25%, 50%, 75% and 100% of your data and measure validation accuracy each time. When the curve flattens, extra data is no longer your bottleneck — your labelling guidelines or the model itself is.",
                    ],
                ],
            ],
            [
                'category' => 'edtech',
                'author'   => 'member@aqdesk.test',
                'title'    => 'Which accessibility issues should a student project fix first to meet WCAG?',
                'body'     => "Our supervisor expects accessibility validation as part of the final report, but WCAG is enormous and we have limited time.\n\nIf we can only fix a handful of things before submission, which ones give the biggest real improvement for users and are most likely to be checked?",
                'tags'     => ['accessibility', 'wcag', 'usability'],
                'answers'  => [
                    [
                        'author' => 'faisal@aqdesk.test',
                        'accept' => true,
                        'votes'  => 5,
                        'body'   => "In rough order of impact for the effort involved:\n\n1. Every form field needs a real <label> tied to it by id. Placeholder text is not a label.\n2. Colour contrast of at least 4.5:1 for body text.\n3. Keyboard reachability — you must be able to tab to every control and see where the focus is.\n4. Alt text on meaningful images, and empty alt on decorative ones.\n5. Correct heading order, one h1 per page.\n\nThose five cover a large share of what an automated checker will flag, and unlike most of WCAG they are quick to retrofit.",
                    ],
                    [
                        'author' => 'expert@aqdesk.test',
                        'accept' => false,
                        'votes'  => 2,
                        'body'   => "Also add a skip-to-content link at the top of the page. It takes two minutes and it is the first thing a keyboard user notices when your navigation has a dozen links.",
                    ],
                ],
            ],
            [
                'category' => 'internet-governance',
                'author'   => 'sara@aqdesk.test',
                'title'    => 'What data protection rules apply to a student platform that stores CVs?',
                'body'     => "Our platform lets users upload a résumé when applying for an internship. We store the file on the server and the application record in MySQL.\n\nWhat are the basic obligations we should be aware of, and what should we write in the report about how we handle this data?",
                'tags'     => ['data-protection', 'privacy', 'policy'],
                'answers'  => [
                    [
                        'author' => 'admin@aqdesk.test',
                        'accept' => false,
                        'votes'  => 2,
                        'body'   => "Three principles cover most of what a student project needs to show:\n\nPurpose limitation — only collect what the application genuinely needs. Storage limitation — say how long you keep a CV and delete it after that. Access control — only the employer who posted the role and an administrator should be able to open the file.\n\nWrite those three as an explicit paragraph in your report; supervisors notice when a project has thought about it at all.",
                    ],
                ],
            ],
            [
                'category' => 'web-development',
                'author'   => 'faisal@aqdesk.test',
                'title'    => 'Should a prototype use a CDN for Bootstrap or a proper build step?',
                'body'     => "We are submitting a prototype that the evaluator will run on their own machine.\n\nA CDN link means the project runs after composer install alone. A build step with Vite is more professional but adds Node.js as a requirement. Which is the better call for a submission?",
                'tags'     => ['bootstrap', 'tooling', 'deployment'],
                'answers'  => [
                    [
                        'author' => 'expert@aqdesk.test',
                        'accept' => true,
                        'votes'  => 3,
                        'body'   => "For something an evaluator has to run on an unfamiliar machine, fewer moving parts wins. A CDN link removes an entire toolchain from your setup instructions, and setup instructions that fail are the most common reason a good project scores badly.\n\nMention the trade-off in your documentation — say you chose the CDN deliberately for reproducibility and that a build pipeline is planned for production. That reads as an engineering decision rather than a shortcut.",
                    ],
                ],
            ],
            [
                'category' => 'edtech',
                'author'   => 'member@aqdesk.test',
                'title'    => 'How do you keep a community Q&A platform from filling up with duplicate questions?',
                'body'     => "Every large Q&A site struggles with duplicates. For a smaller academic community, what mechanisms actually work without annoying new users?",
                'tags'     => ['community', 'moderation', 'ux'],
                'answers'  => [],
            ],
        ];

        foreach ($threads as $index => $thread) {
            $question = Question::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($thread['title'])],
                [
                    'user_id'     => $users[$thread['author']],
                    'category_id' => $categories[$thread['category']],
                    'title'       => $thread['title'],
                    'body'        => $thread['body'],
                    'views_count' => random_int(18, 240),
                ]
            );

            // created_at is guarded (not in $fillable), so it is set directly.
            $question->created_at = now()
                ->subDays(count($threads) - $index)
                ->subHours(random_int(1, 20));
            $question->save();

            $tagIds = collect($thread['tags'])->map(fn ($name) => Tag::findOrCreateByName($name)->id);
            $question->tags()->sync($tagIds);

            foreach ($thread['answers'] as $answerData) {
                $answer = Answer::updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'user_id'     => $users[$answerData['author']],
                    ],
                    [
                        'body'        => $answerData['body'],
                        'is_accepted' => $answerData['accept'],
                    ]
                );

                $answer->created_at = $question->created_at->copy()->addHours(random_int(2, 30));
                $answer->save();

                $this->addVotes($answer, $answerData['votes'], $users);
            }

            // A couple of votes on the question itself.
            $this->addVotes($question, random_int(0, 3), $users);
        }
    }

    /**
     * Attach up to $count upvotes from users who did not write the item.
     *
     * @param  \Illuminate\Support\Collection<string, int>  $users
     */
    private function addVotes(\Illuminate\Database\Eloquent\Model $votable, int $count, $users): void
    {
        $voterIds = $users->values()->reject(fn ($id) => $id === $votable->user_id)->take($count);

        foreach ($voterIds as $voterId) {
            Vote::updateOrCreate(
                [
                    'user_id'      => $voterId,
                    'votable_id'   => $votable->id,
                    'votable_type' => $votable->getMorphClass(),
                ],
                ['value' => Vote::UP]
            );
        }
    }
}

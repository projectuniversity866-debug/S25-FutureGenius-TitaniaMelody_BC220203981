<?php

namespace App\Modules\Knowledge\Database\Seeders;

use App\Modules\Community\Models\Category;
use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Models\Article;
use App\Modules\Knowledge\Models\ExpertVerification;
use App\Modules\Knowledge\Models\LearningPath;
use App\Modules\Knowledge\Models\LearningPathStep;
use App\Modules\Knowledge\Models\LearningResource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo content for expert verification (2.1) and the Knowledge Hub (2.2):
 * one verified expert with articles, and one request waiting for review.
 */
class KnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id', 'email');
        $categories = Category::pluck('id', 'slug');

        $verified = ExpertVerification::firstOrNew(['user_id' => $users['expert@aqdesk.test'], 'status' => 'approved']);
        $verified->forceFill([
            'user_id'     => $users['expert@aqdesk.test'],
            'field'       => 'Machine learning in education',
            'credentials' => "PhD in Computer Science (machine learning). Eight years teaching undergraduate AI courses and supervising final-year projects. Leads the Learning Technology Lab.",
            'evidence_url' => 'https://scholar.example.org/bilal-ahmed',
            'reviewer_id' => $users['admin@aqdesk.test'],
            'review_note' => 'Credentials and publications checked.',
            'reviewed_at' => now()->subDays(20),
        ])->save();

        ExpertVerification::firstOrNew(['user_id' => $users['faisal@aqdesk.test'], 'status' => 'pending'])->forceFill([
            'user_id'      => $users['faisal@aqdesk.test'],
            'field'        => 'Web accessibility',
            'credentials'  => "Two years auditing university websites against WCAG 2.1 AA, and an IAAP CPACC certificate. I run the accessibility workshop for our computing society.",
            'evidence_url' => 'https://portfolio.example.org/faisal',
        ])->save();

        $articles = [
            [
                'title'    => 'How to choose a final-year project that examiners remember',
                'kind'     => 'article',
                'category' => 'edtech',
                'summary'  => 'Scope, evidence and a clear user: what separates a memorable final-year project from a forgettable one.',
                'body'     => "## Start from a real user\n\nThe strongest projects solve a problem for someone you can name: a teacher, a clinic, a student society. Interview them early and write down what success looks like for **them**.\n\n## Keep the scope honest\n\nExaminers reward a small system that is finished, tested and documented over a large one that half works. List your must-haves, then cut a third.\n\n## Collect evidence as you go\n\n- Keep a dated log of decisions and why you made them.\n- Run a usability test with five people and record what went wrong.\n- Measure something: load time, task completion, accessibility issues fixed.\n\n## Write the report while you build\n\nA chapter a fortnight is far easier than a report written in the final week, and it shows the examiner your thinking developed over time.",
                'days'     => 12,
            ],
            [
                'title'    => 'A step-by-step guide to evaluating a classifier properly',
                'kind'     => 'guide',
                'category' => 'artificial-intelligence',
                'summary'  => 'Train/validation/test splits, the right metric for imbalanced data, and how to report results you can defend.',
                'body'     => "## 1. Split before you look\n\nSet aside a test set first and do not touch it until the end. Use a validation set (or cross-validation) for every decision.\n\n## 2. Pick the metric for the problem\n\nAccuracy misleads on imbalanced data. For a rare class, report **precision, recall and F1**, and show the confusion matrix.\n\n```python\nfrom sklearn.metrics import classification_report\nprint(classification_report(y_test, y_pred))\n```\n\n## 3. Compare against a baseline\n\nAlways include a simple baseline (majority class, logistic regression). If your model barely beats it, say so.\n\n## 4. Report uncertainty\n\nRun several seeds or folds and report the mean and spread, not a single best number.",
                'days'     => 6,
            ],
            [
                'title'    => 'Notes on ethics approval for student research (draft)',
                'kind'     => 'article',
                'category' => 'internet-governance',
                'summary'  => 'When a student project needs ethics approval, and how to prepare consent forms and data handling plans.',
                'body'     => "Work in progress. Covers when approval is needed, consent forms, anonymisation and how long to keep data. This draft is visible only to its author and moderators until it is published.",
                'days'     => null,
            ],
        ];

        $this->seedLearningHub($users, $categories);

        foreach ($articles as $data) {
            Article::updateOrCreate(['slug' => Str::slug($data['title'])], [
                'user_id'         => $users['expert@aqdesk.test'],
                'category_id'     => $categories[$data['category']] ?? null,
                'kind'            => $data['kind'],
                'title'           => $data['title'],
                'summary'         => $data['summary'],
                'body'            => $data['body'],
                'reading_minutes' => Article::readingMinutes($data['body']),
                'views_count'     => $data['days'] ? random_int(20, 160) : 0,
                'published_at'    => $data['days'] ? now()->subDays($data['days']) : null,
            ]);
        }
    }

    /** Learning Hub (2.3): a small library, two published paths, and some progress. */
    private function seedLearningHub($users, $categories): void
    {
        $curator = $users['expert@aqdesk.test'];
        $library = [
            ['Web Accessibility Initiative — Introduction to Web Accessibility', 'course', 'beginner', 'https://www.w3.org/WAI/fundamentals/accessibility-intro/', 'W3C WAI', 'What accessibility is, who it helps and the principles behind WCAG.', 30, 'edtech'],
            ['WCAG 2.1 at a glance', 'article', 'beginner', 'https://www.w3.org/WAI/standards-guidelines/wcag/glance/', 'W3C WAI', 'The success criteria summarised on one page — a handy checklist.', 15, 'edtech'],
            ['Testing with a screen reader (NVDA)', 'tutorial', 'intermediate', 'https://www.nvaccess.org/get-help/', 'NV Access', 'Install NVDA and learn the dozen keys you need to test your own pages.', 60, 'edtech'],
            ['MDN — ARIA basics', 'tutorial', 'intermediate', 'https://developer.mozilla.org/en-US/docs/Learn/Accessibility/WAI-ARIA_basics', 'MDN', 'When ARIA helps, when it hurts, and the patterns for common widgets.', 45, 'web-development'],
            ['Machine Learning Crash Course', 'course', 'beginner', 'https://developers.google.com/machine-learning/crash-course', 'Google', 'A fast-paced introduction with videos, exercises and interactive visualisations.', 900, 'artificial-intelligence'],
            ['scikit-learn: Getting started', 'tutorial', 'beginner', 'https://scikit-learn.org/stable/getting_started.html', 'scikit-learn', 'Fit, predict and evaluate your first models in a few lines of Python.', 40, 'artificial-intelligence'],
            ['Model evaluation in scikit-learn', 'article', 'intermediate', 'https://scikit-learn.org/stable/modules/model_evaluation.html', 'scikit-learn', 'Every metric scikit-learn offers, with when to use which.', 50, 'artificial-intelligence'],
        ];

        $resources = [];
        foreach ($library as [$title, $kind, $level, $url, $provider, $description, $minutes, $category]) {
            $resources[$title] = LearningResource::updateOrCreate(['url' => $url], [
                'user_id' => $curator, 'category_id' => $categories[$category] ?? null, 'title' => $title, 'kind' => $kind,
                'level' => $level, 'provider' => $provider, 'description' => $description, 'minutes' => $minutes, 'is_free' => true,
            ]);
        }

        $paths = [
            ['Start with web accessibility', 'edtech', 'beginner', 'From “what is accessibility?” to testing your own pages with a screen reader — four steps for student projects.', [
                ['Web Accessibility Initiative — Introduction to Web Accessibility', 'Start here: the why and the four principles.'],
                ['WCAG 2.1 at a glance', 'Keep this open as your checklist.'],
                ['MDN — ARIA basics', null],
                ['Testing with a screen reader (NVDA)', 'Try it on your own project, not just the demo pages.'],
            ]],
            ['Your first machine learning project', 'artificial-intelligence', 'beginner', 'Learn the basics, build a model, then evaluate it properly — the order examiners expect.', [
                ['Machine Learning Crash Course', 'Do the first five modules; skip the rest for now.'],
                ['scikit-learn: Getting started', null],
                ['Model evaluation in scikit-learn', 'Read the section on precision and recall twice.'],
            ]],
        ];

        foreach ($paths as $i => [$title, $category, $level, $summary, $steps]) {
            $path = LearningPath::updateOrCreate(['slug' => Str::slug($title)], [
                'user_id' => $curator, 'category_id' => $categories[$category] ?? null, 'title' => $title,
                'summary' => $summary, 'level' => $level, 'published_at' => now()->subDays(10 - $i),
            ]);
            foreach ($steps as $position => [$resourceTitle, $note]) {
                $path->steps()->updateOrCreate(
                    ['learning_resource_id' => $resources[$resourceTitle]->id],
                    ['position' => $position + 1, 'note' => $note],
                );
            }
        }

        // The member is two steps into the accessibility path.
        $first = LearningPath::where('slug', 'start-with-web-accessibility')->first()->steps()->take(2)->get();
        $first->each(fn (LearningPathStep $step) => $step->completedBy()->syncWithoutDetaching([$users['member@aqdesk.test']]));
    }
}

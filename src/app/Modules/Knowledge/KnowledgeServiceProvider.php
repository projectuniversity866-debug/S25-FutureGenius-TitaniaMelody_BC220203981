<?php

namespace App\Modules\Knowledge;

use App\Modules\Community\Models\Category;
use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Models\Article;
use App\Modules\Knowledge\Models\ExpertVerification;
use App\Modules\Knowledge\Models\LearningPath;
use App\Modules\Knowledge\Models\LearningResource;
use App\Modules\Knowledge\Policies\ArticlePolicy;
use App\Modules\Knowledge\Policies\ExpertVerificationPolicy;
use App\Modules\Knowledge\Policies\LearningPolicy;
use App\Modules\ModuleServiceProvider;
use App\Modules\Platform\Services\PrivateFiles;
use Illuminate\Support\Facades\DB;

/**
 * Expert knowledge sharing and EdTech: expert verification (roadmap 2.1),
 * the Knowledge Hub of articles and guides (2.2) and the Learning Hub of
 * resources and learning paths (2.3). Content uses Community's four topic
 * categories as a shared taxonomy.
 */
class KnowledgeServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Knowledge';

    protected function policies(): array
    {
        return [
            ExpertVerification::class => ExpertVerificationPolicy::class,
            Article::class            => ArticlePolicy::class,
            LearningResource::class   => LearningPolicy::class,
            LearningPath::class       => LearningPolicy::class,
        ];
    }

    protected function morphMap(): array
    {
        return [
            'expert_verification' => ExpertVerification::class,
            'article'             => Article::class,
            'learning_resource'   => LearningResource::class,
            'learning_path'       => LearningPath::class,
        ];
    }

    /** Your verification requests, articles, curated items and learning progress (roadmap 2.6). */
    protected function personalData(): array
    {
        return [
            'expert_verification' => fn (User $user) => ExpertVerification::where('user_id', $user->id)
                ->get(['field', 'credentials', 'evidence_url', 'status', 'review_note', 'created_at'])->toArray(),
            'articles' => fn (User $user) => Article::where('user_id', $user->id)->get(['title', 'kind', 'summary', 'published_at'])->toArray(),
            'learning_progress' => fn (User $user) => DB::table('learning_progress')
                ->join('learning_path_steps', 'learning_path_steps.id', '=', 'learning_progress.learning_path_step_id')
                ->join('learning_paths', 'learning_paths.id', '=', 'learning_path_steps.learning_path_id')
                ->where('learning_progress.user_id', $user->id)
                ->get(['learning_paths.title as path', 'learning_path_steps.position as step', 'learning_progress.completed_at'])->toArray(),
        ];
    }

    /** Verification requests and their private documents go; published articles stay. */
    protected function personalDataErasers(): array
    {
        return [function (User $user) {
            $files = app(PrivateFiles::class);
            ExpertVerification::where('user_id', $user->id)->get()
                ->each(fn (ExpertVerification $v) => $files->delete($v->document_path))->each->delete();
            DB::table('learning_progress')->where('user_id', $user->id)->delete();
        }];
    }

    protected function relations(): array
    {
        return [
            User::class => [
                // The approved request behind the "Verified expert" badge.
                'expertVerification' => fn (User $user) => $user->hasOne(ExpertVerification::class)
                    ->where('status', 'approved')->latest('reviewed_at'),
                'articles' => fn (User $user) => $user->hasMany(Article::class),
            ],
            Category::class => [
                'articles' => fn (Category $category) => $category->hasMany(Article::class),
            ],
        ];
    }
}

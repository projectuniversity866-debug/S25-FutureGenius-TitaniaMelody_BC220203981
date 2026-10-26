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

<?php

namespace App\Modules\Knowledge\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Models\Article;
use App\Modules\Platform\Services\ActivityLogger;

/** Writing for the Knowledge Hub (roadmap 2.2). */
class ArticleService
{
    public function __construct(private ActivityLogger $activity) {}

    /** @param  array<string, mixed>  $data  validated ArticleRequest data */
    public function create(User $author, array $data, bool $publish): Article
    {
        return Article::create($this->prepare($data) + [
            'user_id'      => $author->id,
            'slug'         => Article::makeSlug($data['title']),
            'published_at' => $publish ? now() : null,
        ]);
    }

    /**
     * Publishing keeps the first publication date; switching back to draft
     * takes it off the hub.
     *
     * @param  array<string, mixed>  $data  validated ArticleRequest data
     */
    public function update(Article $article, array $data, bool $publish): Article
    {
        $article->update($this->prepare($data) + [
            'slug'         => Article::makeSlug($data['title'], $article->id),
            'published_at' => $publish ? ($article->published_at ?? now()) : null,
        ]);

        return $article;
    }

    /** A moderator removing someone else's article is logged. */
    public function delete(Article $article, User $by): void
    {
        if ($by->id !== $article->user_id) {
            $this->activity->record('article.deleted', $article, 'Deleted the article “'.$article->title.'”',
                ['author' => $article->author->name]);
        }

        $article->delete();
    }

    public function recordView(Article $article): void
    {
        $article->increment('views_count');
    }

    private function prepare(array $data): array
    {
        return $data + ['reading_minutes' => Article::readingMinutes($data['body'])];
    }
}

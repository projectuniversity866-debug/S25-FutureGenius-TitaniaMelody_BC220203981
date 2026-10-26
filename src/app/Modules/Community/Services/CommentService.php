<?php

namespace App\Modules\Community\Services;

use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Comment;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Notifications\NewComment;
use App\Modules\Identity\Models\User;

class CommentService
{
    public function add(Question|Answer $post, User $author, string $body): Comment
    {
        $comment = $post->comments()->create(['user_id' => $author->id, 'body' => trim($body)]);
        $comment->setRelation('commentable', $post)->setRelation('user', $author);

        // The post's author hears about it — unless they wrote the comment.
        if ($post->user_id !== $author->id) {
            $post->user->notify(new NewComment($comment));
        }

        return $comment;
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}

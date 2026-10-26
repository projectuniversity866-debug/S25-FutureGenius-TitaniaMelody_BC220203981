<?php

namespace App\Modules\Knowledge\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Models\LearningPath;
use App\Modules\Knowledge\Models\LearningPathStep;
use App\Modules\Knowledge\Models\LearningResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Learning Hub (roadmap 2.3): curating the resource library and learning
 * paths, and each member's progress through a path.
 */
class LearningService
{
    // ---------------------------------------------------------- resources

    /** @param  array<string, mixed>  $data  validated LearningResourceRequest data */
    public function createResource(User $curator, array $data): LearningResource
    {
        return LearningResource::create($data + ['user_id' => $curator->id]);
    }

    /** @param  array<string, mixed>  $data  validated LearningResourceRequest data */
    public function updateResource(LearningResource $resource, array $data): LearningResource
    {
        $resource->update($data);

        return $resource;
    }

    /** Removing a resource also removes it from every path (foreign key cascade). */
    public function deleteResource(LearningResource $resource): void
    {
        $resource->delete();
    }

    // -------------------------------------------------------------- paths

    /** @param  array<string, mixed>  $data  validated LearningPathRequest data */
    public function createPath(User $curator, array $data): LearningPath
    {
        // A new path starts as a draft: it has no steps to follow yet.
        return LearningPath::create($data + [
            'user_id' => $curator->id,
            'slug'    => LearningPath::makeSlug($data['title']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data  validated LearningPathRequest data
     *
     * @throws ValidationException when publishing a path without steps
     */
    public function updatePath(LearningPath $path, array $data, bool $publish): LearningPath
    {
        if ($publish && $path->steps()->doesntExist()) {
            throw ValidationException::withMessages(['publish' => 'Add at least one step before publishing.']);
        }

        $path->update($data + [
            'slug'         => LearningPath::makeSlug($data['title'], $path->id),
            'published_at' => $publish ? ($path->published_at ?? now()) : null,
        ]);

        return $path;
    }

    public function deletePath(LearningPath $path): void
    {
        $path->delete();
    }

    /** @throws ValidationException when the resource is already a step */
    public function addStep(LearningPath $path, int $resourceId, ?string $note): LearningPathStep
    {
        if ($path->steps()->where('learning_resource_id', $resourceId)->exists()) {
            throw ValidationException::withMessages(['learning_resource_id' => 'That resource is already a step in this path.']);
        }

        return $path->steps()->create([
            'learning_resource_id' => $resourceId,
            'position'             => (int) $path->steps()->max('position') + 1,
            'note'                 => $note,
        ]);
    }

    /** Swap a step with its neighbour ("up" or "down"); nothing happens at either end. */
    public function moveStep(LearningPathStep $step, string $direction): void
    {
        $neighbour = LearningPathStep::where('learning_path_id', $step->learning_path_id)
            ->where('position', $direction === 'up' ? '<' : '>', $step->position)
            ->orderBy('position', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if (! $neighbour) {
            return;
        }

        DB::transaction(function () use ($step, $neighbour) {
            [$a, $b] = [$step->position, $neighbour->position];
            $step->update(['position' => $b]);
            $neighbour->update(['position' => $a]);
        });
    }

    /** Remove a step and close the gap in the numbering. */
    public function removeStep(LearningPathStep $step): void
    {
        DB::transaction(function () use ($step) {
            LearningPathStep::where('learning_path_id', $step->learning_path_id)
                ->where('position', '>', $step->position)->decrement('position');
            $step->delete();
        });
    }

    // ----------------------------------------------------------- progress

    /** Tick or untick a step for the member; returns whether it is now done. */
    public function toggleStep(User $user, LearningPathStep $step): bool
    {
        $done = $step->completedBy()->whereKey($user->id)->exists();

        $done ? $step->completedBy()->detach($user->id) : $step->completedBy()->attach($user->id, ['completed_at' => now()]);

        return ! $done;
    }

    /** @return array<int, int>  ids of the path's steps the member has completed */
    public function completedStepIds(?User $user, LearningPath $path): array
    {
        if (! $user) {
            return [];
        }

        return DB::table('learning_progress')->where('user_id', $user->id)
            ->whereIn('learning_path_step_id', $path->steps()->select('id'))
            ->pluck('learning_path_step_id')->map('intval')->all();
    }

    /**
     * Published paths the member has started, with done / total counts.
     *
     * @return Collection<int, array{path: LearningPath, done: int, total: int}>
     */
    public function startedBy(User $user): Collection
    {
        $doneByPath = DB::table('learning_progress')
            ->join('learning_path_steps', 'learning_path_steps.id', '=', 'learning_progress.learning_path_step_id')
            ->where('learning_progress.user_id', $user->id)
            ->groupBy('learning_path_steps.learning_path_id')
            ->selectRaw('learning_path_steps.learning_path_id as path_id, count(*) as done')
            ->pluck('done', 'path_id');

        return LearningPath::published()->whereKey($doneByPath->keys())->withCount('steps')->get()
            ->map(fn (LearningPath $path) => ['path' => $path, 'done' => (int) $doneByPath[$path->id], 'total' => $path->steps_count]);
    }
}

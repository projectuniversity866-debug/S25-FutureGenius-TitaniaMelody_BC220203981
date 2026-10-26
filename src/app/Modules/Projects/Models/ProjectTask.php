<?php

namespace App\Modules\Projects\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A card on a project's task board (roadmap 3.5). */
class ProjectTask extends Model
{
    /** status => [column title, icon] */
    public const STATUSES = [
        'todo'  => ['To do', 'circle'],
        'doing' => ['Doing', 'arrow-repeat'],
        'done'  => ['Done', 'check-circle'],
    ];

    /** priority => [label, icon, badge] */
    public const PRIORITIES = [
        'high'   => ['High', 'arrow-up', 'text-bg-danger'],
        'normal' => ['Normal', 'dash', 'text-bg-light'],
        'low'    => ['Low', 'arrow-down', 'text-bg-light'],
    ];

    protected $fillable = ['created_by', 'assignee_id', 'title', 'description', 'status', 'priority', 'due_on', 'completed_at'];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'completed_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** Board order inside a column: high priority first, then the nearest due date, then oldest. */
    public function scopeBoardOrder(Builder $query): Builder
    {
        return $query->orderByRaw("case priority when 'high' then 0 when 'normal' then 1 else 2 end")
            ->orderByRaw('due_on is null')->orderBy('due_on')->orderBy('id');
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'done' && $this->due_on && $this->due_on->lt(today());
    }
}

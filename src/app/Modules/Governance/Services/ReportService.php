<?php

namespace App\Modules\Governance\Services;

use App\Modules\Governance\Models\ModerationAction;
use App\Modules\Governance\Models\Report;
use App\Modules\Governance\Notifications\ContentRemoved;
use App\Modules\Governance\Notifications\NewReport;
use App\Modules\Governance\Notifications\ReportResolved;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Contracts\Reportable;
use App\Modules\Platform\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Report → moderation queue → decision (roadmap 2.5). A decision covers
 * every open report on the same content, is written to the moderation log
 * and the activity log, and is told to the author and the reporters.
 */
class ReportService
{
    public function __construct(private ActivityLogger $activity) {}

    /** The reportable item behind a "type/id" pair from a URL; 404 for anything else. */
    public function resolve(string $type, int $id): Model&Reportable
    {
        $class = in_array($type, Report::REPORTABLE, true) ? Relation::getMorphedModel($type) : null;
        abort_unless($class && is_subclass_of($class, Reportable::class), 404);

        return $class::findOrFail($id);
    }

    /** @throws ValidationException for your own content or a second open report */
    public function file(User $reporter, Model&Reportable $item, string $reason, ?string $details): Report
    {
        if ($item->reportAuthor()?->is($reporter)) {
            throw ValidationException::withMessages(['reason' => 'You cannot report your own content.']);
        }

        $open = Report::where('reportable_type', $item->getMorphClass())->where('reportable_id', $item->getKey())
            ->where('status', 'open');
        if ((clone $open)->where('user_id', $reporter->id)->exists()) {
            throw ValidationException::withMessages(['reason' => 'You have already reported this. Moderators will review it.']);
        }

        $report = new Report(['reason' => $reason, 'details' => $details]);
        $report->reporter()->associate($reporter);
        $report->reportable()->associate($item);
        $report->save();

        User::query()->where('is_active', true)
            ->whereHas('role.permissions', fn ($q) => $q->where('slug', 'content.moderate'))
            ->each(fn (User $moderator) => $moderator->notify(new NewReport($report, $item->reportLabel())));

        return $report;
    }

    /**
     * "remove" deletes the content through its own module; "dismiss" keeps it.
     *
     * @throws ValidationException when the report was already decided
     */
    public function decide(Report $report, User $moderator, string $decision, ?string $note): ModerationAction
    {
        if ($report->status !== 'open') {
            throw ValidationException::withMessages(['decision' => 'This report has already been decided.']);
        }

        $item = $report->reportable;
        $label = $item?->reportLabel() ?? 'Content already removed';
        $author = $item?->reportAuthor();
        $removed = $decision === 'remove';

        [$action, $reports] = DB::transaction(function () use ($report, $moderator, $note, $item, $label, $removed) {
            $reports = Report::with('reporter')->where('reportable_type', $report->reportable_type)
                ->where('reportable_id', $report->reportable_id)->where('status', 'open')->get();

            if ($removed && $item) {
                $item->removeAsModerator($moderator);
            }

            Report::whereKey($reports->modelKeys())->update([
                'status' => $removed ? 'actioned' : 'dismissed', 'moderator_id' => $moderator->id,
                'decision_note' => $note, 'decided_at' => now(),
            ]);

            $action = new ModerationAction([
                'subject_type' => $report->reportable_type, 'subject_id' => $report->reportable_id,
                'subject_label' => $label, 'action' => $removed ? 'removed' : 'dismissed',
                'reason' => $report->reason, 'note' => $note, 'reports_count' => $reports->count(),
            ]);
            $action->moderator()->associate($moderator)->save();

            return [$action, $reports];
        });

        $this->activity->record('moderation.'.$action->action, null,
            ($removed ? 'Removed ' : 'Kept ').$label.' after '.$reports->count().' '.str('report')->plural($reports->count()),
            ['reason' => $report->reason, 'note' => $note]);

        if ($removed && $author && ! $author->is($moderator)) {
            $author->notify(new ContentRemoved($label, $report->reasonLabel(), $note));
        }
        $reports->pluck('reporter')->filter()->unique('id')
            ->each(fn (User $reporter) => $reporter->notify(new ReportResolved($label, $removed)));

        return $action;
    }
}

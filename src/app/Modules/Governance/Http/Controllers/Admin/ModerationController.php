<?php

namespace App\Modules\Governance\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Http\Requests\ModerationDecisionRequest;
use App\Modules\Governance\Models\ModerationAction;
use App\Modules\Governance\Models\Report;
use App\Modules\Governance\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The moderation queue and the decision log (roadmap 2.5). */
class ModerationController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), Report::STATUSES, true) ? $request->query('status') : 'open';

        $reports = Report::with('reporter', 'reportable')
            ->where('status', $status)
            ->when($status === 'open', fn ($q) => $q->oldest(), fn ($q) => $q->latest('decided_at'))
            ->paginate(20)
            ->withQueryString();

        $counts = Report::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('governance::admin.reports', compact('reports', 'status', 'counts'));
    }

    public function show(Report $report): View
    {
        $report->load('reporter', 'moderator', 'reportable');
        $related = Report::with('reporter')->where('reportable_type', $report->reportable_type)
            ->where('reportable_id', $report->reportable_id)->whereKeyNot($report->id)->latest()->get();

        return view('governance::admin.report', compact('report', 'related'));
    }

    public function decide(ModerationDecisionRequest $request, Report $report): RedirectResponse
    {
        $action = $this->reports->decide($report, $request->user(), $request->validated('decision'), $request->validated('note'));

        return redirect()->route('admin.reports.index')->with('status', $action->action === 'removed'
            ? 'Content removed. The author and the reporters were told.'
            : 'Report dismissed; the content stays. The reporters were told.');
    }

    /** Every decision ever made, newest first. */
    public function log(): View
    {
        $actions = ModerationAction::with('moderator')->latest('created_at')->latest('id')->paginate(25);

        return view('governance::admin.log', compact('actions'));
    }
}

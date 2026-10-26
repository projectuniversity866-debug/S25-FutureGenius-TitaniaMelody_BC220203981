<?php

namespace App\Modules\Governance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Http\Requests\ReportRequest;
use App\Modules\Governance\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Members report content that breaks the community guidelines (roadmap 2.5). */
class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function create(string $type, int $id): View
    {
        $item = $this->reports->resolve($type, $id);

        return view('governance::reports.create', compact('item', 'type', 'id'));
    }

    public function store(ReportRequest $request, string $type, int $id): RedirectResponse
    {
        $item = $this->reports->resolve($type, $id);
        $this->reports->file($request->user(), $item, $request->validated('reason'), $request->validated('details'));

        return redirect()->to($item->reportUrl())
            ->with('status', 'Thanks — your report was sent to the moderators. You will be told what they decide.');
    }
}

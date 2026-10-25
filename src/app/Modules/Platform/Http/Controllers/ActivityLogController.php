<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Platform\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Administrator view of the audit trail. Behind can:activity.view. */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = $request->query('action');

        $entries = ActivityLog::with('user')
            ->when($action, fn ($q) => $q->where('action', $action))
            ->latest('created_at')->latest('id')
            ->paginate(25)
            ->withQueryString();

        $actions = ActivityLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('platform::admin.activity', compact('entries', 'actions'));
    }
}

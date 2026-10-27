<?php

namespace App\Modules\Communications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communications\Models\Announcement;
use App\Modules\Communications\Services\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** "Updates": current and past announcements, and closing a banner (FR7, roadmap 3.18). */
class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementService $announcements) {}

    public function index(Request $request): View
    {
        $base = fn () => Announcement::for($request->user())->where('starts_at', '<=', now());
        $current = $base()->live()->latest('starts_at')->get();
        $past = $base()->whereNotNull('ends_at')->where('ends_at', '<=', now())->latest('starts_at')->take(20)->get();

        return view('communications::announcements.index', compact('current', 'past'));
    }

    public function dismiss(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->announcements->dismiss($announcement, $request->user(), $request->session());

        return back();
    }
}

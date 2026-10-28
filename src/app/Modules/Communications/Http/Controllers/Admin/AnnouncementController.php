<?php

namespace App\Modules\Communications\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Communications\Http\Requests\AnnouncementRequest;
use App\Modules\Communications\Models\Announcement;
use App\Modules\Communications\Services\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Administration → Announcements (FR7 "Community updates", roadmap 3.18). */
class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementService $announcements) {}

    public function index(): View
    {
        $announcements = Announcement::withCount('dismissals')->latest('starts_at')->paginate(20);

        return view('communications::admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('communications::admin.announcements.form', ['announcement' => null]);
    }

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        $announcement = $this->announcements->create($request->user(), $request->announcementData());

        return redirect()->route('admin.announcements.index')->with('status', $announcement->phase() === 'live' ? 'Announcement posted; it shows on every page now.' : 'Announcement saved.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('communications::admin.announcements.form', compact('announcement'));
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->announcements->update($announcement, $request->announcementData());

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement saved.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->announcements->delete($announcement);

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement deleted.');
    }
}

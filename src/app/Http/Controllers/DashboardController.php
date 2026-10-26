<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The role-aware dashboard: each role sees the figures and lists that
 * matter to its work. The numbers come from DashboardMetrics.
 */
class DashboardController extends Controller
{
    public function index(Request $request, DashboardMetrics $metrics): View
    {
        $user = $request->user()->load(['role', 'profile']);

        [$view, $data] = match (true) {
            $user->isAdmin()  => ['dashboard.admin', $metrics->admin()],
            $user->isExpert() => ['dashboard.expert', $metrics->expert($user)],
            default           => ['dashboard.member', $metrics->member($user)],
        };

        return view($view, [
            'user'     => $user,
            'days'     => DashboardMetrics::DAYS,
            'todo'     => $metrics->todo($user),
            'feed'     => $metrics->activityFeed($user),
        ] + $data);
    }
}

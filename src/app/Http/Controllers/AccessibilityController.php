<?php

namespace App\Http\Controllers;

use App\Support\AccessibilityPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

/**
 * Accessibility statement and display preferences (roadmap 2.7). Works for
 * visitors and members alike, and without JavaScript: the toolbar is a plain
 * form posting here; with JavaScript it applies changes instantly.
 */
class AccessibilityController extends Controller
{
    public function show(Request $request): View
    {
        return view('accessibility', ['prefs' => AccessibilityPreferences::fromRequest($request)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $prefs = $request->boolean('reset')
            ? AccessibilityPreferences::parse('')
            : AccessibilityPreferences::fromInput($request->all());

        // One year, readable by the page's script (excluded from cookie encryption in bootstrap/app.php).
        Cookie::queue(Cookie::make(AccessibilityPreferences::COOKIE, $prefs->toCookieValue(), 60 * 24 * 365, null, null, null, false));

        return back()->with('status', $request->boolean('reset') ? 'Display settings reset.' : 'Display settings saved.');
    }
}

<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Platform\Services\MarkdownRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The editor's "Preview" tab — same renderer as the published page. */
class MarkdownPreviewController extends Controller
{
    public function __invoke(Request $request, MarkdownRenderer $markdown): JsonResponse
    {
        $request->validate(['text' => ['nullable', 'string', 'max:20000']]);

        return response()->json(['html' => (string) $markdown->render($request->input('text'))]);
    }
}

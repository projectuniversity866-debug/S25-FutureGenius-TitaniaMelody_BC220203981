<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controllers stay thin (docs/architecture/ARCHITECTURE.md §7.1):
 * a Form Request validates, a Policy authorises, a Service does the work.
 */
abstract class Controller
{
    use AuthorizesRequests;
}

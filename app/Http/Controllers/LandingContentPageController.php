<?php

namespace App\Http\Controllers;

use App\Models\LandingContent;
use Illuminate\Contracts\View\View;

/**
 * Renders the Blade/Alpine shell for a Super Admin's public landing page
 * content editor. Data loads client-side against /api/v1/landing-content.
 */
class LandingContentPageController extends Controller
{
    public function index(): View
    {
        $this->authorize('view', LandingContent::current());

        return view('landing-content.index');
    }
}

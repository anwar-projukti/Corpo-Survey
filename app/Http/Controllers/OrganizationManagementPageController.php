<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Contracts\View\View;

/**
 * Renders the Blade/Alpine shell for a Super Admin's organizations list
 * (spec §13 "System Admin"). All data loads client-side against
 * /api/v1/organizations.
 */
class OrganizationManagementPageController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Organization::class);

        return view('organizations.index');
    }
}

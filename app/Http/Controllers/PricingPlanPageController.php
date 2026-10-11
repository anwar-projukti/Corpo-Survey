<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use Illuminate\Contracts\View\View;

/**
 * Renders the Blade/Alpine shell for a Super Admin's pricing plan
 * management. All data loads client-side against /api/v1/pricing-plans.
 */
class PricingPlanPageController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', PricingPlan::class);

        return view('pricing-plans.index');
    }
}

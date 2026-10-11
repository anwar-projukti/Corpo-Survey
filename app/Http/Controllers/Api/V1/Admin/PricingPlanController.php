<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PricingPlans\StorePricingPlanRequest;
use App\Http\Requests\PricingPlans\UpdatePricingPlanRequest;
use App\Http\Requests\Surveys\ReorderRequest;
use App\Models\PricingPlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Public landing page pricing tiers (spec: "one basic, one enterprise",
 * admin-managed) — a genuine list, unlike LandingContent's single row.
 * Every action is gated by PricingPlanPolicy, which only Super Admin ever
 * passes.
 */
class PricingPlanController extends Controller
{
    public function index(): Collection
    {
        $this->authorize('viewAny', PricingPlan::class);

        return PricingPlan::query()->orderBy('order')->get();
    }

    public function store(StorePricingPlanRequest $request): PricingPlan
    {
        $maxOrder = PricingPlan::query()->max('order');

        $plan = PricingPlan::create([
            ...$request->validated(),
            'order' => $maxOrder === null ? 0 : $maxOrder + 1,
            'updated_by' => $request->user()->id,
        ]);

        return $plan;
    }

    public function update(UpdatePricingPlanRequest $request, PricingPlan $pricingPlan): PricingPlan
    {
        $pricingPlan->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return $pricingPlan->fresh();
    }

    public function destroy(PricingPlan $pricingPlan): Response
    {
        $this->authorize('delete', $pricingPlan);

        $pricingPlan->delete();

        return response()->noContent();
    }

    public function reorder(ReorderRequest $request): Response
    {
        $this->authorize('viewAny', PricingPlan::class);

        $orderedIds = $request->validated('order');
        $existingIds = PricingPlan::query()->pluck('id');

        if ($existingIds->count() !== count($orderedIds) || $existingIds->diff($orderedIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'order' => 'The submitted order must contain exactly the current set of plans, no more or fewer.',
            ]);
        }

        DB::transaction(function () use ($orderedIds) {
            foreach (array_values($orderedIds) as $position => $id) {
                PricingPlan::query()->whereKey($id)->update(['order' => $position]);
            }
        });

        return response()->noContent();
    }
}

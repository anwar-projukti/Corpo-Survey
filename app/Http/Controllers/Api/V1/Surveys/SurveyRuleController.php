<?php

namespace App\Http\Controllers\Api\V1\Surveys;

use App\Http\Controllers\Controller;
use App\Http\Requests\Surveys\StoreRuleRequest;
use App\Http\Requests\Surveys\UpdateRuleRequest;
use App\Http\Resources\Surveys\SurveyRuleResource;
use App\Models\SurveyRule;
use App\Models\SurveyVersion;
use Illuminate\Http\JsonResponse;

class SurveyRuleController extends Controller
{
    public function store(StoreRuleRequest $request, SurveyVersion $version): SurveyRuleResource
    {
        $maxOrder = $version->rules()->max('order');

        $rule = $version->rules()->create([
            ...$request->validated(),
            'order' => $request->validated('order') ?? ($maxOrder === null ? 0 : $maxOrder + 1),
        ]);

        return new SurveyRuleResource($rule);
    }

    public function update(UpdateRuleRequest $request, SurveyRule $rule): SurveyRuleResource
    {
        $rule->update($request->validated());

        return new SurveyRuleResource($rule->fresh());
    }

    public function destroy(SurveyRule $rule): JsonResponse
    {
        $this->authorize('edit', $rule->version);

        $rule->delete();

        return response()->json(['success' => true, 'message' => 'Rule deleted.']);
    }
}

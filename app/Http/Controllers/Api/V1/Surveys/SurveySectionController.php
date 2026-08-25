<?php

namespace App\Http\Controllers\Api\V1\Surveys;

use App\Actions\Surveys\ReorderItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Surveys\ReorderRequest;
use App\Http\Requests\Surveys\StoreSectionRequest;
use App\Http\Requests\Surveys\UpdateSectionRequest;
use App\Http\Resources\Surveys\SurveySectionResource;
use App\Models\SurveySection;
use App\Models\SurveyVersion;
use Illuminate\Http\JsonResponse;

class SurveySectionController extends Controller
{
    public function store(StoreSectionRequest $request, SurveyVersion $version): SurveySectionResource
    {
        $maxOrder = $version->sections()->max('order');

        $section = $version->sections()->create([
            ...$request->validated(),
            'order' => $maxOrder === null ? 0 : $maxOrder + 1,
        ]);

        return new SurveySectionResource($section->load('questions.options'));
    }

    public function update(UpdateSectionRequest $request, SurveySection $section): SurveySectionResource
    {
        $section->update($request->validated());

        return new SurveySectionResource($section->fresh()->load('questions.options'));
    }

    public function destroy(SurveySection $section): JsonResponse
    {
        $this->authorize('edit', $section->version);

        $section->delete();

        return response()->json(['success' => true, 'message' => 'Section deleted.']);
    }

    public function reorder(ReorderRequest $request, SurveyVersion $version): JsonResponse
    {
        $this->authorize('edit', $version);

        app(ReorderItems::class)->handle($version->sections(), $request->validated('order'));

        return response()->json(['success' => true]);
    }
}

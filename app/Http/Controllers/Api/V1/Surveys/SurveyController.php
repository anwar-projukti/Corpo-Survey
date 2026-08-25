<?php

namespace App\Http\Controllers\Api\V1\Surveys;

use App\Actions\Surveys\CreateNewDraftVersion;
use App\Actions\Surveys\CreateSurvey;
use App\Actions\Surveys\GeneratePublicSurveyLink;
use App\Actions\Surveys\PublishSurveyVersion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Surveys\StoreSurveyRequest;
use App\Http\Requests\Surveys\UpdateSurveyRequest;
use App\Http\Resources\Surveys\SurveyResource;
use App\Http\Resources\Surveys\SurveyVersionResource;
use App\Models\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SurveyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Survey::class);

        $surveys = Survey::query()
            ->with(['currentDraftVersion', 'currentPublishedVersion'])
            ->latest()
            ->paginate(min((int) $request->integer('per_page', 20), 100));

        return SurveyResource::collection($surveys);
    }

    public function store(StoreSurveyRequest $request, CreateSurvey $createSurvey): SurveyResource
    {
        $survey = $createSurvey->handle($request->user(), $request->validated());

        return new SurveyResource($survey->load(['currentDraftVersion', 'currentPublishedVersion']));
    }

    public function show(Survey $survey): SurveyResource
    {
        $this->authorize('view', $survey);

        return new SurveyResource($survey->load([
            'currentDraftVersion.sections.questions.options',
            'currentDraftVersion.rules',
            'currentPublishedVersion.sections.questions.options',
            'currentPublishedVersion.rules',
        ]));
    }

    public function update(UpdateSurveyRequest $request, Survey $survey): SurveyResource
    {
        $survey->update($request->validated());

        return new SurveyResource($survey->fresh(['currentDraftVersion', 'currentPublishedVersion']));
    }

    public function destroy(Survey $survey): JsonResponse
    {
        $this->authorize('delete', $survey);

        $survey->delete();

        return response()->json(['success' => true, 'message' => 'Survey deleted.']);
    }

    public function publish(Survey $survey, PublishSurveyVersion $publishSurveyVersion): SurveyResource
    {
        $this->authorize('publish', $survey);

        $survey = $publishSurveyVersion->handle($survey, request()->user());

        return new SurveyResource($survey->load(['currentDraftVersion', 'currentPublishedVersion']));
    }

    public function createDraft(Survey $survey, CreateNewDraftVersion $createNewDraftVersion): SurveyVersionResource
    {
        $this->authorize('update', $survey);

        $draft = $createNewDraftVersion->handle($survey);

        return new SurveyVersionResource($draft->load(['sections.questions.options', 'rules']));
    }

    public function publicLink(Survey $survey, GeneratePublicSurveyLink $generatePublicSurveyLink): JsonResponse
    {
        $this->authorize('view', $survey);

        if (! $survey->isPublished()) {
            return response()->json(['message' => 'This survey has no published version to link to.'], 422);
        }

        return response()->json(['url' => $generatePublicSurveyLink->handle($survey)]);
    }
}

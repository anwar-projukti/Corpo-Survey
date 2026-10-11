<?php

namespace App\Http\Controllers\Api\V1\Surveys;

use App\Actions\Surveys\CloseSurvey;
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
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SurveyController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Survey::class);

        // ?trashed=1 shows only soft-deleted surveys (the "Deleted surveys"
        // list a user restores from) instead of the ordinary list — kept as
        // a separate query rather than merged into one list, the same
        // reasoning as OrganizationController::index().
        $surveys = Survey::query()
            ->when(
                $request->boolean('trashed'),
                fn ($query) => $query->onlyTrashed(),
            )
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
        $before = $survey->only(['title', 'description']);

        $survey->update($request->validated());

        $this->auditLogger->log('survey.updated', subject: $survey, properties: [
            'before' => $before,
            'after' => $survey->only(['title', 'description']),
        ], actor: $request->user());

        return new SurveyResource($survey->fresh(['currentDraftVersion', 'currentPublishedVersion']));
    }

    public function destroy(Request $request, Survey $survey): JsonResponse
    {
        $this->authorize('delete', $survey);

        $this->auditLogger->log('survey.deleted', subject: $survey, properties: [
            'title' => $survey->title,
        ], actor: $request->user());

        $survey->delete();

        return $this->successResponse('Survey deleted.');
    }

    public function restore(Request $request, int $survey): SurveyResource
    {
        $survey = Survey::onlyTrashed()->findOrFail($survey);

        $this->authorize('restore', $survey);

        $survey->restore();

        $this->auditLogger->log('survey.restored', subject: $survey, properties: [
            'title' => $survey->title,
        ], actor: $request->user());

        return new SurveyResource($survey->load(['currentDraftVersion', 'currentPublishedVersion']));
    }

    public function publish(Survey $survey, PublishSurveyVersion $publishSurveyVersion): SurveyResource
    {
        $this->authorize('publish', $survey);

        $survey = $publishSurveyVersion->handle($survey, request()->user());

        return new SurveyResource($survey->load(['currentDraftVersion', 'currentPublishedVersion']));
    }

    public function close(Request $request, Survey $survey, CloseSurvey $closeSurvey): SurveyResource
    {
        $this->authorize('close', $survey);

        $survey = $closeSurvey->handle($survey, $request->user());

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
            return $this->errorResponse('This survey has no published version to link to.');
        }

        return response()->json(['success' => true, 'url' => $generatePublicSurveyLink->handle($survey)]);
    }
}

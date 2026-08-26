<?php

namespace App\Http\Controllers\Api\V1\Surveys;

use App\Http\Controllers\Controller;
use App\Http\Requests\Surveys\StoreSurveyAssignmentRequest;
use App\Http\Resources\Surveys\SurveyAssignmentResource;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SurveyAssignmentController extends Controller
{
    public function index(Survey $survey): AnonymousResourceCollection
    {
        $this->authorize('assign', $survey);

        return SurveyAssignmentResource::collection(
            $survey->assignments()->with(['user', 'assignedBy'])->latest()->get()
        );
    }

    public function store(StoreSurveyAssignmentRequest $request, Survey $survey): SurveyAssignmentResource
    {
        $assignment = $survey->assignments()->create([
            'user_id' => $request->validated('user_id'),
            'assigned_by' => $request->user()->id,
        ]);

        return new SurveyAssignmentResource($assignment->load(['user', 'assignedBy']));
    }

    public function destroy(Survey $survey, SurveyAssignment $assignment): JsonResponse
    {
        $this->authorize('assign', $survey);

        abort_unless($assignment->survey_id === $survey->id, 404);

        $assignment->delete();

        return response()->json(['success' => true, 'message' => 'Assignment removed.']);
    }
}

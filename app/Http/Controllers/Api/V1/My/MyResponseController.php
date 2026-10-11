<?php

namespace App\Http\Controllers\Api\V1\My;

use App\Actions\Surveys\SubmitSurveyResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\My\StoreMyResponseRequest;
use App\Http\Resources\Responses\SurveyResponseResource;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Door-to-door response submission and self-view (spec §7) — the same
 * validation/rule engine as the public path (App\Actions\Surveys\SubmitSurveyResponse),
 * but authenticated: collected_by_user_id and collection_method are
 * derived from the logged-in field user, never from client input, and
 * there's no CAPTCHA/OTP/respondent-cookie layer since the submitter's
 * identity is already verified by the session itself.
 *
 * index()/show() are deliberately scoped to collected_by_user_id = the
 * authenticated user rather than reusing SurveyResponsePolicy's
 * organization-wide response.view — a Field User seeing every response
 * anyone in their org collected (the admin response table's job) is a
 * different capability from a Field User reviewing their own work, and
 * this class only ever grants the latter.
 */
class MyResponseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $responses = SurveyResponse::query()
            ->where('collected_by_user_id', $request->user()->id)
            ->when($request->integer('survey_id'), fn ($query, $surveyId) => $query->where('survey_id', $surveyId))
            ->with('survey:id,title')
            ->latest('submitted_at')
            ->paginate(20);

        return SurveyResponseResource::collection($responses);
    }

    public function show(Request $request, SurveyResponse $response): SurveyResponseResource|JsonResponse
    {
        if ($response->collected_by_user_id !== $request->user()->id) {
            abort(403, 'This response was not collected by you.');
        }

        $response->load('survey:id,title', 'answers', 'collector');

        return new SurveyResponseResource($response);
    }

    public function store(StoreMyResponseRequest $request, Survey $survey, SubmitSurveyResponse $submit): JsonResponse
    {
        $version = $survey->currentPublishedVersion;

        if (! $version || $survey->isClosed()) {
            return $this->errorResponse('This survey is closed and no longer accepting responses.', 404);
        }

        $version->load(['sections.questions.options', 'rules']);

        try {
            $surveyResponse = $submit->handle(
                $version,
                $this->normalizeAnswerKeys($request->array('answers')),
                context: [
                    'latitude' => $request->float('latitude') ?: null,
                    'longitude' => $request->float('longitude') ?: null,
                    'started_at' => $request->date('started_at'),
                ],
                collector: $request->user(),
                clientSubmissionId: $request->string('client_submission_id')->value() ?: null,
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Field agent survey submission failed.', ['exception' => $e]);

            return $this->errorResponse('Something went wrong. Please try again.');
        }

        return $this->successResponse('Response recorded.', [
            'status' => $surveyResponse->status->value,
            'id' => $surveyResponse->id,
        ], 201);
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeAnswerKeys(array $answers): array
    {
        $normalized = [];

        foreach ($answers as $questionId => $value) {
            $normalized[(int) $questionId] = $value;
        }

        return $normalized;
    }
}

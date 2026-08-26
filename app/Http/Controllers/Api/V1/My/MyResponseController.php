<?php

namespace App\Http\Controllers\Api\V1\My;

use App\Actions\Surveys\SubmitSurveyResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\My\StoreMyResponseRequest;
use App\Models\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Door-to-door response submission (spec §7) — the same validation/rule
 * engine as the public path (App\Actions\Surveys\SubmitSurveyResponse),
 * but authenticated: collected_by_user_id and collection_method are
 * derived from the logged-in field user, never from client input, and
 * there's no CAPTCHA/OTP/respondent-cookie layer since the submitter's
 * identity is already verified by the session itself.
 */
class MyResponseController extends Controller
{
    public function store(StoreMyResponseRequest $request, Survey $survey, SubmitSurveyResponse $submit): JsonResponse
    {
        $version = $survey->currentPublishedVersion;

        if (! $version) {
            return response()->json(['message' => 'This survey has no published version.'], 404);
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

            return response()->json(['message' => 'Something went wrong. Please try again.'], 422);
        }

        return response()->json([
            'message' => 'Response recorded.',
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

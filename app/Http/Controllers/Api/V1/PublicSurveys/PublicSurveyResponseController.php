<?php

namespace App\Http\Controllers\Api\V1\PublicSurveys;

use App\Actions\Surveys\SubmitSurveyResponse;
use App\Contracts\CaptchaVerifier;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSurveys\SubmitResponseRequest;
use App\Models\Survey;
use App\Services\PublicSurveyAccess;
use App\Services\SurveyOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PublicSurveyResponseController extends Controller
{
    public function store(
        SubmitResponseRequest $request,
        Survey $survey,
        SubmitSurveyResponse $submit,
        PublicSurveyAccess $access,
        CaptchaVerifier $captcha,
        SurveyOtpService $otp,
    ): JsonResponse {
        $version = $survey->currentPublishedVersion;

        if (! $version) {
            return response()->json(['message' => __('surveys.public.closed_body')], 404);
        }

        $version->load(['sections.questions.options', 'rules']);

        if (! $captcha->verify($request->string('captcha_token')->value() ?: null, $request->ip())) {
            return response()->json(['message' => __('surveys.public.captcha_failed')], 422);
        }

        $settings = $version->settingsData;

        if ($settings->require_otp) {
            $destination = $request->string('otp_destination')->value();

            if (! $destination || ! $otp->isVerified($version, $destination)) {
                return response()->json(['message' => __('surveys.otp.invalid_code')], 422);
            }
        }

        $fingerprint = $access->fingerprint($request);

        if ($access->hasReachedDuplicateLimit($version, $fingerprint)) {
            return response()->json(['message' => __('surveys.public.duplicate_body')], 409);
        }

        try {
            $surveyResponse = $submit->handle(
                $version,
                $this->normalizeAnswerKeys($request->array('answers')),
                $fingerprint,
                context: [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'latitude' => $request->float('latitude') ?: null,
                    'longitude' => $request->float('longitude') ?: null,
                    'started_at' => $request->date('started_at'),
                ],
                anonymize: $settings->anonymize_responses,
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Public survey submission failed.', ['exception' => $e]);

            return response()->json(['message' => __('surveys.public.validation_error')], 422);
        }

        $response = response()->json([
            'message' => __('surveys.public.thank_you_body'),
            'status' => $surveyResponse->status->value,
        ], 201);

        return $access->withRespondentCookie($request, $response);
    }

    /**
     * Answer keys arrive over JSON as strings ("12"); RuleEngine and
     * AnswerValidationRules key everything by the question's real integer
     * id, so this is where that gets normalized once, up front.
     *
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

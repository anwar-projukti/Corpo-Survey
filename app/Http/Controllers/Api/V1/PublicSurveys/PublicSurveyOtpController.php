<?php

namespace App\Http\Controllers\Api\V1\PublicSurveys;

use App\Contracts\CaptchaVerifier;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSurveys\RequestOtpRequest;
use App\Http\Requests\PublicSurveys\VerifyOtpRequest;
use App\Models\Survey;
use App\Services\SurveyOtpService;
use Illuminate\Http\JsonResponse;

class PublicSurveyOtpController extends Controller
{
    public function send(RequestOtpRequest $request, Survey $survey, SurveyOtpService $otp, CaptchaVerifier $captcha): JsonResponse
    {
        $version = $survey->currentPublishedVersion;

        if (! $version || ! $version->settingsData->require_otp) {
            return response()->json(['message' => __('surveys.public.closed_body')], 404);
        }

        if (! $captcha->verify($request->string('captcha_token')->value() ?: null, $request->ip())) {
            return response()->json(['message' => __('surveys.public.captcha_failed')], 422);
        }

        $otp->send($version, $request->string('destination')->value());

        return response()->json(['message' => __('surveys.otp.sent_confirmation')]);
    }

    public function verify(VerifyOtpRequest $request, Survey $survey, SurveyOtpService $otp): JsonResponse
    {
        $version = $survey->currentPublishedVersion;

        if (! $version || ! $version->settingsData->require_otp) {
            return response()->json(['message' => __('surveys.public.closed_body')], 404);
        }

        $verified = $otp->verify(
            $version,
            $request->string('destination')->value(),
            $request->string('code')->value(),
        );

        if (! $verified) {
            return response()->json(['message' => __('surveys.otp.invalid_code')], 422);
        }

        return response()->json(['message' => __('surveys.otp.sent_confirmation')]);
    }
}

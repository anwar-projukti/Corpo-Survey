<?php

namespace App\Services;

use App\Enums\DuplicatePolicy;
use App\Enums\ResponseStatus;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Identifies an anonymous public-survey respondent across requests without
 * ever requiring a login (spec §5.8). Identity is a random token in an
 * encrypted, HTTP-only cookie set on first visit — never the raw IP/UA,
 * which are only a fallback for a respondent who has no cookie at all
 * (private browsing, cookies blocked). What's persisted to
 * survey_responses.respondent_fingerprint is always a one-way hash of the
 * identity, never the identity itself.
 */
class PublicSurveyAccess
{
    private const COOKIE_NAME = 'respondent_id';

    private const COOKIE_MINUTES = 60 * 24 * 365;

    public function fingerprint(Request $request): string
    {
        $identity = $this->readCookieToken($request) ?? $this->fallbackIdentity($request);

        return hash('sha256', $identity);
    }

    /**
     * Only actually sets the cookie when the incoming request didn't
     * already carry a valid one — call this once the response is built, so
     * a repeat visit keeps the same identity instead of rotating it.
     */
    public function withRespondentCookie(Request $request, Response|JsonResponse $response): Response|JsonResponse
    {
        if ($this->readCookieToken($request) !== null) {
            return $response;
        }

        return $response->withCookie(
            Cookie::make(self::COOKIE_NAME, (string) Str::uuid(), self::COOKIE_MINUTES, httpOnly: true, sameSite: 'lax')
        );
    }

    /**
     * True when this fingerprint has already submitted a complete response
     * to this version and the version's duplicate_policy forbids another.
     * Field-agent-collected responses (respondent_fingerprint is null) are
     * never counted — the policy only governs public self-serve submission.
     */
    public function hasReachedDuplicateLimit(SurveyVersion $version, string $fingerprint): bool
    {
        if ($version->settingsData->duplicate_policy !== DuplicatePolicy::OneResponsePerUser) {
            return false;
        }

        return SurveyResponse::query()
            ->where('survey_version_id', $version->id)
            ->where('respondent_fingerprint', $fingerprint)
            ->where('status', ResponseStatus::Complete)
            ->exists();
    }

    private function readCookieToken(Request $request): ?string
    {
        // The EncryptCookies middleware (part of the "web" group, which
        // these public routes must run through) decrypts and validates
        // this before the controller ever sees it — read it as plain text.
        $raw = $request->cookie(self::COOKIE_NAME);

        return is_string($raw) && $raw !== '' ? $raw : null;
    }

    private function fallbackIdentity(Request $request): string
    {
        return 'ip-ua:'.$request->ip().'|'.$request->userAgent();
    }
}

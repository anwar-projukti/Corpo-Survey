<?php

namespace App\Actions\Surveys;

use App\Models\Survey;
use Illuminate\Support\Facades\URL;

/**
 * The public respondent-facing link for a survey (spec §5.8) — a Laravel
 * signed URL rather than a stored token, so no extra column or lookup is
 * needed and links can't be enumerated. Always points at whichever version
 * is current_published_version at request time, so an admin can share one
 * link that keeps working across republishes.
 */
class GeneratePublicSurveyLink
{
    public function handle(Survey $survey, ?\DateTimeInterface $expiresAt = null): string
    {
        return $expiresAt
            ? URL::temporarySignedRoute('public.surveys.show', $expiresAt, ['survey' => $survey->id])
            : URL::signedRoute('public.surveys.show', ['survey' => $survey->id]);
    }
}

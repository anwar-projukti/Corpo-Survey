<?php

namespace App\Enums;

/**
 * How many times the same respondent may submit a given survey (spec
 * §5.8). "Same respondent" for an anonymous public submission is the
 * signed respondent cookie set on first visit — see
 * App\Services\PublicSurveyAccess.
 */
enum DuplicatePolicy: string
{
    case MultipleResponses = 'multiple_responses';
    case OneResponsePerUser = 'one_response_per_user';
}

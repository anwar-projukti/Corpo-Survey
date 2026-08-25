<?php

namespace App\Enums;

/**
 * Public survey submission access mode, admin-configurable per survey
 * (spec §5.8 and §6's collection settings).
 */
enum SurveyAccessMode: string
{
    case MultipleResponses = 'multiple_responses';
    case OneResponsePerUser = 'one_response_per_user';
    case RequireAuthentication = 'require_authentication';
    case RequireOtp = 'require_otp';
    case Anonymous = 'anonymous';
}

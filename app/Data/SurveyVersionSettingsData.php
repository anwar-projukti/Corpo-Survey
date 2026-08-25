<?php

namespace App\Data;

use App\Enums\DuplicatePolicy;
use Spatie\LaravelData\Data;

/**
 * The shape of survey_versions.settings — public-facing behavior for this
 * version's respondent experience (spec §5.8, §3.2).
 *
 * These are independent settings, not one mutually-exclusive mode: an
 * admin can require an OTP *and* cap it to one response per person, for
 * instance. require_authentication isn't modeled here — public submission
 * is inherently unauthenticated; a logged-in field user submitting on a
 * respondent's behalf (spec §7) is a separate flow with its own
 * collected_by_user_id-based tracking, not a public-link setting.
 */
class SurveyVersionSettingsData extends Data
{
    public function __construct(
        public DuplicatePolicy $duplicate_policy = DuplicatePolicy::MultipleResponses,

        // Require a one-time code (sent to an email/phone the respondent
        // provides) before they can submit — spec §5.8's "Require OTP".
        public bool $require_otp = false,

        // Strip IP address and user agent from the stored response's meta
        // — spec §5.11's "anonymized survey mode strips IP/user-agent".
        public bool $anonymize_responses = false,

        public bool $one_question_per_screen = false,
        public bool $show_progress_bar = true,
        public bool $allow_save_and_resume = true,
    ) {}
}

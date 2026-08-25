<?php

namespace App\Data;

use App\Enums\SurveyAccessMode;
use Spatie\LaravelData\Data;

/**
 * The shape of survey_versions.settings. Public-facing behavior that
 * belongs to a specific version rather than the question tree — building
 * this out fully (CAPTCHA provider config, OTP delivery, etc.) is Phase 5's
 * job; this phase only needs the shape to exist and be validated so later
 * phases have a settled contract to build against.
 */
class SurveyVersionSettingsData extends Data
{
    public function __construct(
        public SurveyAccessMode $access_mode = SurveyAccessMode::MultipleResponses,

        public bool $one_question_per_screen = false,
        public bool $show_progress_bar = true,
        public bool $allow_save_and_resume = true,
    ) {}
}

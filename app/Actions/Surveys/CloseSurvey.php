<?php

namespace App\Actions\Surveys;

use App\Models\Survey;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Closes a survey — stops new public/field submissions against its
 * published version without touching that version's own status (draft/
 * published/archived) or any existing response data. Distinct from
 * publish/draft, which move which version is "current"; closing only ever
 * affects whether new responses may still come in.
 */
class CloseSurvey
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(Survey $survey, ?User $actor = null): Survey
    {
        if ($survey->isClosed()) {
            throw ValidationException::withMessages([
                'survey' => 'This survey is already closed.',
            ]);
        }

        $survey->setClosedAt(now());
        $survey = $survey->fresh();

        $this->auditLogger->log('survey.closed', subject: $survey, actor: $actor ?? Auth::user());

        return $survey;
    }
}

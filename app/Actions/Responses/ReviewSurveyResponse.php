<?php

namespace App\Actions\Responses;

use App\Enums\ResponseReviewStatus;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\AuditLogger;

/**
 * Approve, reject, or archive a response (spec §8) — the only sanctioned
 * writer of SurveyResponse::review_status (see that model's $fillable
 * docblock), so every transition is captured in the audit log with the
 * before/after values, who made the change, and when (spec §5.11).
 */
class ReviewSurveyResponse
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(SurveyResponse $response, ResponseReviewStatus $newStatus, User $actor): SurveyResponse
    {
        $previousStatus = $response->review_status;

        if ($previousStatus === $newStatus) {
            return $response;
        }

        $response->setReviewStatus($newStatus);

        $this->auditLogger->log(
            'response.review_status_changed',
            subject: $response,
            properties: [
                'before' => $previousStatus->value,
                'after' => $newStatus->value,
            ],
            actor: $actor,
        );

        return $response->fresh();
    }
}

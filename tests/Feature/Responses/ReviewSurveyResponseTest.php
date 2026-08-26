<?php

use App\Actions\Responses\ReviewSurveyResponse;
use App\Enums\ResponseReviewStatus;
use App\Models\AuditLog;
use App\Models\SurveyResponse;
use App\Models\User;

test('reviewing a response changes its review_status', function () {
    $response = SurveyResponse::factory()->create();
    $actor = User::factory()->create(['organization_id' => $response->organization_id]);

    $updated = app(ReviewSurveyResponse::class)->handle($response, ResponseReviewStatus::Approved, $actor);

    expect($updated->review_status)->toBe(ResponseReviewStatus::Approved)
        ->and($response->fresh()->review_status)->toBe(ResponseReviewStatus::Approved);
});

test('reviewing a response writes an audit log entry with before and after values', function () {
    $response = SurveyResponse::factory()->create();
    $actor = User::factory()->create(['organization_id' => $response->organization_id]);

    app(ReviewSurveyResponse::class)->handle($response, ResponseReviewStatus::Rejected, $actor);

    $log = AuditLog::where('action', 'response.review_status_changed')->first();

    expect($log)->not->toBeNull()
        ->and($log->actor_id)->toBe($actor->id)
        ->and($log->subject_type)->toBe((new SurveyResponse)->getMorphClass())
        ->and($log->subject_id)->toBe($response->id)
        ->and($log->properties['before'])->toBe('pending')
        ->and($log->properties['after'])->toBe('rejected');
});

test('reviewing a response to the same status it already has is a no-op with no audit entry', function () {
    $response = SurveyResponse::factory()->reviewStatus(ResponseReviewStatus::Approved)->create();
    $actor = User::factory()->create(['organization_id' => $response->organization_id]);

    app(ReviewSurveyResponse::class)->handle($response, ResponseReviewStatus::Approved, $actor);

    expect(AuditLog::where('action', 'response.review_status_changed')->count())->toBe(0);
});

test('a response can move through the full pending approved archived lifecycle with each transition audited', function () {
    $response = SurveyResponse::factory()->create();
    $actor = User::factory()->create(['organization_id' => $response->organization_id]);
    $action = app(ReviewSurveyResponse::class);

    $action->handle($response, ResponseReviewStatus::Approved, $actor);
    $action->handle($response->fresh(), ResponseReviewStatus::Archived, $actor);

    expect($response->fresh()->review_status)->toBe(ResponseReviewStatus::Archived)
        ->and(AuditLog::where('action', 'response.review_status_changed')->count())->toBe(2);
});

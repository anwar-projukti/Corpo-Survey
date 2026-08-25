<?php

namespace App\Actions\Surveys;

use App\Enums\SurveyVersionStatus;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Publishes a survey's current draft version: the previously-published
 * version (if any) is archived, the draft becomes published, and the
 * survey's current_published_version_id pointer moves to it. The survey
 * is left with no draft — starting a new one is a separate, explicit step
 * (CreateNewDraftVersion), matching the edit-in-place-on-draft workflow
 * from spec §6.2.
 */
class PublishSurveyVersion
{
    public function handle(Survey $survey, User $publisher): Survey
    {
        $draft = $survey->currentDraftVersion;

        if (! $draft || ! $draft->isDraft()) {
            throw ValidationException::withMessages([
                'survey' => 'This survey has no draft version to publish.',
            ]);
        }

        if (! $draft->sections()->whereHas('questions')->exists()) {
            throw ValidationException::withMessages([
                'survey' => 'A survey must have at least one question before it can be published.',
            ]);
        }

        return DB::transaction(function () use ($survey, $publisher, $draft) {
            $previouslyPublished = $survey->currentPublishedVersion;

            if ($previouslyPublished) {
                $previouslyPublished->forceFill(['status' => SurveyVersionStatus::Archived])->save();
            }

            $draft->forceFill([
                'status' => SurveyVersionStatus::Published,
                'published_by' => $publisher->id,
                'published_at' => now(),
            ])->save();

            $survey->setCurrentDraftAndPublishedVersions(draft: null, published: $draft);

            return $survey->fresh();
        });
    }
}

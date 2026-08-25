<?php

namespace Database\Seeders;

use App\Data\SurveyVersionSettingsData;
use App\Enums\QuestionType;
use App\Enums\RuleAction;
use App\Enums\SurveyVersionStatus;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds one realistic multi-section survey per organization, purely via
 * factories/models — no UI exists yet (that's Phase 4). Demonstrates the
 * dynamic question architecture end to end: sections, several question
 * types including one with options and one with conditional config, and a
 * conditional rule, across both a draft and a published version.
 */
class SurveySeeder extends Seeder
{
    public function run(): void
    {
        Organization::all()->each(function (Organization $organization) {
            $admin = $organization->users()->first();

            if (! $admin) {
                return;
            }

            $this->seedCustomerSatisfactionSurvey($organization, $admin);
        });
    }

    private function seedCustomerSatisfactionSurvey(Organization $organization, User $admin): void
    {
        // Idempotent: this seeder must be safe to re-run (e.g. `php
        // artisan db:seed` a second time in CI or local dev) without
        // tripping the (organization_id, slug) unique index.
        $survey = Survey::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'customer-satisfaction-survey')
            ->first();

        if ($survey) {
            if ($survey->versions()->exists()) {
                return;
            }
        } else {
            // organization_id is deliberately not mass-assignable (see
            // Survey::$fillable) — forceFill it directly rather than
            // relying on BelongsToOrganization's creating() hook, which
            // only fires for the currently-authenticated user and this
            // seeder isn't run within a request/auth context.
            $survey = new Survey([
                'created_by' => $admin->id,
                'title' => 'Customer Satisfaction Survey',
                'slug' => 'customer-satisfaction-survey',
                'description' => 'Quarterly check-in on how satisfied customers are with our service.',
            ]);
            $survey->forceFill(['organization_id' => $organization->id])->save();
        }

        $publishedVersion = $this->buildVersion($survey, versionNumber: 1, status: SurveyVersionStatus::Published, publishedBy: $admin);

        // A draft exists on top of the published version, matching the
        // edit-in-place-on-draft workflow: an Org Admin is midway through
        // adding a new section to the next revision.
        $draftVersion = $this->buildVersion($survey, versionNumber: 2, status: SurveyVersionStatus::Draft, publishedBy: null);

        $survey->setCurrentDraftAndPublishedVersions($draftVersion, $publishedVersion);
    }

    private function buildVersion(Survey $survey, int $versionNumber, SurveyVersionStatus $status, ?User $publishedBy): SurveyVersion
    {
        $version = SurveyVersion::create([
            'survey_id' => $survey->id,
            'version_number' => $versionNumber,
            'status' => $status,
            // The DTO's own defaults, not a hand-copied literal — stays in
            // sync automatically if SurveyVersionSettingsData's defaults change.
            'settings' => (new SurveyVersionSettingsData)->toArray(),
            'published_by' => $publishedBy?->id,
            'published_at' => $publishedBy ? now() : null,
        ]);

        $aboutYou = $version->sections()->create([
            'title' => 'About You',
            'description' => 'A few quick details before we get started.',
            'order' => 0,
        ]);

        $aboutYou->questions()->create([
            'type' => QuestionType::Number,
            'label' => 'What is your age?',
            'is_required' => true,
            'order' => 0,
            'config' => ['min' => 13, 'max' => 120],
        ]);

        $aboutYou->questions()->create([
            'type' => QuestionType::Email,
            'label' => 'What is your email address?',
            'is_required' => false,
            'order' => 1,
        ]);

        $satisfaction = $version->sections()->create([
            'title' => 'Your Experience',
            'order' => 1,
        ]);

        $satisfaction->questions()->create([
            'type' => QuestionType::Satisfaction,
            'label' => 'How satisfied are you with our service?',
            'is_required' => true,
            'order' => 0,
            'config' => [
                'scaleMin' => 1,
                'scaleMax' => 5,
                'scaleLabels' => ['Very Dissatisfied', 'Dissatisfied', 'Neutral', 'Satisfied', 'Very Satisfied'],
            ],
        ]);

        $recommendQuestion = $satisfaction->questions()->create([
            'type' => QuestionType::SingleChoice,
            'label' => 'Would you recommend us to a friend?',
            'is_required' => true,
            'order' => 1,
        ]);

        $recommendQuestion->options()->create(['label' => 'Yes', 'value' => 'yes', 'order' => 0]);
        $recommendNo = $recommendQuestion->options()->create(['label' => 'No', 'value' => 'no', 'order' => 1]);

        $followUp = $satisfaction->questions()->create([
            'type' => QuestionType::LongText,
            'label' => 'What would encourage you to recommend us?',
            'is_required' => false,
            'order' => 2,
            'config' => ['maxLength' => 1000],
        ]);

        $satisfaction->questions()->create([
            'type' => QuestionType::Nps,
            'label' => 'On a scale of 0–10, how likely are you to recommend us?',
            'is_required' => false,
            'order' => 3,
            'config' => ['scaleMin' => 0, 'scaleMax' => 10],
        ]);

        // IF "Would you recommend us?" = No THEN show the follow-up
        // long-text question (spec §6.3). Compares against the option's
        // `value` column, not its row id — that's what a real submitted
        // answer stores (see question_options migration).
        $version->rules()->create([
            'name' => 'Show follow-up when not likely to recommend',
            'conditions' => [
                'question_id' => $recommendQuestion->id,
                'operator' => 'equals',
                'value' => $recommendNo->value,
            ],
            'action' => RuleAction::Show,
            'target' => ['question_id' => $followUp->id],
            'order' => 0,
        ]);

        return $version;
    }
}

<?php

namespace Database\Factories;

use App\Enums\CollectionMethod;
use App\Enums\ResponseReviewStatus;
use App\Enums\ResponseStatus;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyResponse>
 */
class SurveyResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-1 week', 'now');

        return [
            'organization_id' => Organization::factory(),
            'survey_id' => Survey::factory(),
            'survey_version_id' => SurveyVersion::factory(),
            'respondent_id' => null,
            'collection_method' => CollectionMethod::SelfServe,
            'status' => ResponseStatus::Complete,
            // Explicit rather than relying on the DB column default: a
            // factory-created model's in-memory instance never reflects a
            // DB-side default value (Eloquent's create() doesn't re-fetch
            // generated columns), so review_status would otherwise read as
            // null in-memory even though the row itself is correctly
            // 'pending' — surprising for anything that reads it right off
            // the object create() just returned instead of a fresh() copy.
            'review_status' => ResponseReviewStatus::Pending,
            'respondent_fingerprint' => hash('sha256', fake()->uuid()),
            'meta' => [],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'started_at' => $startedAt,
            'submitted_at' => fake()->dateTimeBetween($startedAt, 'now'),
        ];
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ResponseStatus::Partial,
            'submitted_at' => null,
        ]);
    }

    public function anonymized(): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => null,
            'user_agent' => null,
        ]);
    }

    public function collectedByFieldAgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_method' => CollectionMethod::FieldAgent,
            'collected_by_user_id' => User::factory(),
            'respondent_fingerprint' => null,
        ]);
    }

    public function reviewStatus(ResponseReviewStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'review_status' => $status,
        ]);
    }
}

<?php

namespace App\Actions\Surveys;

use App\Enums\SurveyVersionStatus;
use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates a new survey and its initial (version 1) draft version in one
 * transaction. A brand-new survey always starts as a draft — there is
 * nothing to publish yet.
 */
class CreateSurvey
{
    /**
     * @param  array{title: string, description?: string|null}  $attributes
     */
    public function handle(User $creator, array $attributes): Survey
    {
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                return DB::transaction(function () use ($creator, $attributes, $attempt) {
                    // organization_id is deliberately not mass-assignable
                    // (see Survey::$fillable) — forceFill it from the
                    // explicit $creator parameter rather than depending on
                    // BelongsToOrganization's creating() hook, which only
                    // fires for the ambient Auth::user(), not necessarily
                    // the $creator this action was called with.
                    $survey = new Survey([
                        'created_by' => $creator->id,
                        'title' => $attributes['title'],
                        'slug' => $this->uniqueSlug($creator->organization_id, $attributes['title'], random: $attempt > 1),
                        'description' => $attributes['description'] ?? null,
                    ]);
                    $survey->forceFill(['organization_id' => $creator->organization_id])->save();

                    $version = SurveyVersion::create([
                        'survey_id' => $survey->id,
                        'version_number' => 1,
                        'status' => SurveyVersionStatus::Draft,
                    ]);

                    $survey->setCurrentDraftVersion($version);

                    return $survey;
                });
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }
    }

    private function uniqueSlug(int $organizationId, string $title, bool $random = false): string
    {
        $base = Str::slug($title);
        $slug = $random ? $base.'-'.Str::random(6) : $base;
        $suffix = 1;

        while (Survey::withoutGlobalScopes()->where('organization_id', $organizationId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix).($random ? '-'.Str::random(6) : '');
        }

        return $slug;
    }
}

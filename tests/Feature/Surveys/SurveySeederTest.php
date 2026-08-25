<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;
use Database\Seeders\SurveySeeder;

test('the survey seeder is safe to run more than once', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    (new SurveySeeder)->run();
    (new SurveySeeder)->run();

    expect(Survey::where('organization_id', $organization->id)->count())->toBe(1);
});

test('the survey seeder creates a survey with a published and a draft version', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    (new SurveySeeder)->run();

    $survey = Survey::where('organization_id', $organization->id)->first();

    expect($survey)->not->toBeNull()
        ->and($survey->isPublished())->toBeTrue()
        ->and($survey->currentDraftVersion->isDraft())->toBeTrue()
        ->and($survey->currentPublishedVersion->sections()->count())->toBeGreaterThan(0);
});

test('the seeded conditional rule compares against the options value, not its row id', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    (new SurveySeeder)->run();

    $survey = Survey::where('organization_id', $organization->id)->first();
    $rule = $survey->currentPublishedVersion->rules()->first();

    expect($rule->conditions['value'])->toBe('no');
});

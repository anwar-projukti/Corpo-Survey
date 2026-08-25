<?php

use App\Enums\DuplicatePolicy;
use App\Enums\QuestionType;
use App\Mail\SurveyOtpCode;
use App\Models\Organization;
use App\Models\ResponseOtp;
use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

function buildPublicSurvey(array $settings = []): array
{
    $organization = Organization::factory()->create();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create([
        'survey_id' => $survey->id,
        'settings' => $settings,
    ]);
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $question = $section->questions()->create([
        'type' => QuestionType::ShortText,
        'label' => 'Your name',
        'order' => 0,
        'is_required' => true,
    ]);

    $survey->setCurrentPublishedVersion($version);

    return [$survey->fresh(), $version->fresh(['sections.questions']), $question];
}

test('a signed public link renders the survey page', function () {
    [$survey] = buildPublicSurvey();

    $url = URL::signedRoute('public.surveys.show', ['survey' => $survey->id]);

    $response = $this->get($url);

    $response->assertOk()->assertSee($survey->title);
});

test('an unsigned request to the public survey page is rejected', function () {
    [$survey] = buildPublicSurvey();

    $response = $this->get("/s/{$survey->id}");

    $response->assertForbidden();
});

test('a tampered signature is rejected', function () {
    [$survey] = buildPublicSurvey();

    $url = URL::signedRoute('public.surveys.show', ['survey' => $survey->id]);
    $tampered = str_replace('signature=', 'signature=tampered', $url);

    $response = $this->get($tampered);

    $response->assertForbidden();
});

test('the public api rejects requests without first visiting the signed page', function () {
    [$survey] = buildPublicSurvey();

    $response = $this->getJson("/api/v1/public/surveys/{$survey->id}");

    $response->assertForbidden();
});

test('visiting the signed page grants session access to that surveys public api', function () {
    [$survey, , $question] = buildPublicSurvey();

    $url = URL::signedRoute('public.surveys.show', ['survey' => $survey->id]);
    $this->get($url)->assertOk();

    $response = $this->getJson("/api/v1/public/surveys/{$survey->id}");

    $response->assertOk()
        ->assertJsonPath('data.title', $survey->title)
        ->assertJsonPath('data.version.sections.0.questions.0.id', $question->id);
});

test('session access granted for one survey does not extend to another', function () {
    [$surveyA] = buildPublicSurvey();
    [$surveyB] = buildPublicSurvey();

    $urlA = URL::signedRoute('public.surveys.show', ['survey' => $surveyA->id]);
    $this->get($urlA)->assertOk();

    $response = $this->getJson("/api/v1/public/surveys/{$surveyB->id}");

    $response->assertForbidden();
});

test('a full submission flow succeeds end to end', function () {
    [$survey, , $question] = buildPublicSurvey();

    $url = URL::signedRoute('public.surveys.show', ['survey' => $survey->id]);
    $this->get($url)->assertOk();

    $response = $this->postJson("/api/v1/public/surveys/{$survey->id}/responses", [
        'answers' => [(string) $question->id => 'Jane Doe'],
    ]);

    $response->assertCreated()->assertJsonPath('status', 'complete');

    expect($survey->responses()->count())->toBe(1)
        ->and($survey->responses()->first()->answers()->first()->value)->toBe('Jane Doe');
});

test('submitting with a missing required answer returns a validation error', function () {
    [$survey] = buildPublicSurvey();

    $url = URL::signedRoute('public.surveys.show', ['survey' => $survey->id]);
    $this->get($url)->assertOk();

    $response = $this->postJson("/api/v1/public/surveys/{$survey->id}/responses", ['answers' => []]);

    $response->assertStatus(422);
});

test('a duplicate submission is rejected once one response per user is reached', function () {
    [$survey, , $question] = buildPublicSurvey(['duplicate_policy' => DuplicatePolicy::OneResponsePerUser->value]);

    $url = URL::signedRoute('public.surveys.show', ['survey' => $survey->id]);
    $this->get($url)->assertOk();

    $this->postJson("/api/v1/public/surveys/{$survey->id}/responses", [
        'answers' => [(string) $question->id => 'First response'],
    ])->assertCreated();

    $response = $this->postJson("/api/v1/public/surveys/{$survey->id}/responses", [
        'answers' => [(string) $question->id => 'Second response'],
    ]);

    $response->assertStatus(409);
});

test('a survey with no published version returns not found', function () {
    $organization = Organization::factory()->create();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $url = URL::signedRoute('public.surveys.show', ['survey' => $survey->id]);
    $this->get($url)->assertOk();

    $response = $this->getJson("/api/v1/public/surveys/{$survey->id}");

    $response->assertNotFound();
});

test('submission requires otp verification when the version requires it', function () {
    Mail::fake();
    [$survey, $version, $question] = buildPublicSurvey(['require_otp' => true]);

    $url = URL::signedRoute('public.surveys.show', ['survey' => $survey->id]);
    $this->get($url)->assertOk();

    $unverified = $this->postJson("/api/v1/public/surveys/{$survey->id}/responses", [
        'answers' => [(string) $question->id => 'Jane Doe'],
        'otp_destination' => 'jane@example.com',
    ]);
    $unverified->assertStatus(422);

    $this->postJson("/api/v1/public/surveys/{$survey->id}/otp", ['destination' => 'jane@example.com'])->assertOk();
    Mail::assertSent(SurveyOtpCode::class);

    $otp = ResponseOtp::where('destination', 'jane@example.com')->first();
    $otp->forceFill(['code_hash' => Hash::make('123456')])->save();

    $this->postJson("/api/v1/public/surveys/{$survey->id}/otp/verify", [
        'destination' => 'jane@example.com',
        'code' => '123456',
    ])->assertOk();

    $verified = $this->postJson("/api/v1/public/surveys/{$survey->id}/responses", [
        'answers' => [(string) $question->id => 'Jane Doe'],
        'otp_destination' => 'jane@example.com',
    ]);

    $verified->assertCreated();
});

test('an admin can fetch a public link for a published survey', function () {
    [$survey] = buildPublicSurvey();
    $user = User::factory()->create(['organization_id' => $survey->organization_id]);
    $user->assignRole('Org Admin');

    $response = $this->actingAs($user)->getJson("/api/v1/surveys/{$survey->id}/public-link");

    $response->assertOk();
    expect($response->json('url'))->toContain("/s/{$survey->id}")
        ->and($response->json('url'))->toContain('signature=');
});

test('fetching a public link for an unpublished survey fails', function () {
    $organization = Organization::factory()->create();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Org Admin');

    $response = $this->actingAs($user)->getJson("/api/v1/surveys/{$survey->id}/public-link");

    $response->assertStatus(422);
});

test('an admin from a different organization cannot fetch a surveys public link', function () {
    [$survey] = buildPublicSurvey();
    $otherOrg = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $otherOrg->id]);
    $user->assignRole('Org Admin');

    $response = $this->actingAs($user)->getJson("/api/v1/surveys/{$survey->id}/public-link");

    // OrganizationScope filters the row out of the query entirely before
    // route-model binding resolves it, so this 404s rather than 403s —
    // the same behavior SurveyPolicyTest documents for every other survey
    // endpoint under normal (scoped) route-model binding.
    $response->assertNotFound();
});

test('the public responses route is wired through csrf verification like every other stateful write path', function () {
    // VerifyCsrfToken/ValidateCsrfToken short-circuits during unit tests
    // (Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::runningUnitTests()),
    // so a real end-to-end request here can't actually trigger a 419 —
    // asserting the route's resolved middleware stack is what proves this
    // route group didn't quietly drop CSRF the way it deliberately dropped
    // "signed" in favor of RequirePublicSurveyAccess.
    $route = Route::getRoutes()->getByName('public.surveys.responses.store');

    expect($route->gatherMiddleware())->toContain(ValidateCsrfToken::class);
});

test('otp send and verify use a tighter rate limit than the rest of the public survey api', function () {
    $otpRoute = Route::getRoutes()->getByName('public.surveys.otp.send');
    $showRoute = Route::getRoutes()->getByName('public.surveys.api.show');

    expect($otpRoute->gatherMiddleware())->toContain('throttle:public-survey-otp')
        ->and($showRoute->gatherMiddleware())->not->toContain('throttle:public-survey-otp');
});

test('the public-survey-otp limiter allows far fewer attempts per minute than the general public-survey limiter', function () {
    $request = Request::create('/', 'POST', ['destination' => 'respondent@example.com']);
    $request->setLaravelSession(app('session.store'));

    $otpLimit = app(RateLimiter::class)->limiter('public-survey-otp')($request);
    $generalLimit = app(RateLimiter::class)->limiter('public-survey')($request);

    expect($otpLimit->maxAttempts)->toBeLessThan($generalLimit->maxAttempts);
});

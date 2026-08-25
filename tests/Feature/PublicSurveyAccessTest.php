<?php

use App\Enums\DuplicatePolicy;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Services\PublicSurveyAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

function requestWithSession(): Request
{
    $request = Request::create('/');
    $request->setLaravelSession(app('session.store'));

    return $request;
}

test('two requests with the same respondent cookie produce the same fingerprint', function () {
    $service = new PublicSurveyAccess;

    $request = Request::create('/');
    $request->cookies->set('respondent_id', 'same-token-value');

    expect($service->fingerprint($request))->toBe($service->fingerprint($request));
});

test('two requests with different respondent cookies produce different fingerprints', function () {
    $service = new PublicSurveyAccess;

    $requestA = Request::create('/');
    $requestA->cookies->set('respondent_id', 'token-a');

    $requestB = Request::create('/');
    $requestB->cookies->set('respondent_id', 'token-b');

    expect($service->fingerprint($requestA))->not->toBe($service->fingerprint($requestB));
});

test('a request with no cookie falls back to ip and user agent for its fingerprint', function () {
    $service = new PublicSurveyAccess;

    $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.9', 'HTTP_USER_AGENT' => 'TestAgent/1.0']);

    $fingerprint = $service->fingerprint($request);

    expect($fingerprint)->toBe(hash('sha256', 'ip-ua:203.0.113.9|TestAgent/1.0'));
});

test('the fingerprint is a one way hash, never the raw cookie value', function () {
    $service = new PublicSurveyAccess;

    $request = Request::create('/');
    $request->cookies->set('respondent_id', 'super-secret-identity-token');

    expect($service->fingerprint($request))->not->toContain('super-secret-identity-token');
});

test('a respondent cookie is only set when the request did not already carry one', function () {
    $service = new PublicSurveyAccess;

    $requestWithoutCookie = Request::create('/');
    $response = $service->withRespondentCookie($requestWithoutCookie, new Response);
    expect($response->headers->getCookies())->toHaveCount(1);

    $requestWithCookie = Request::create('/');
    $requestWithCookie->cookies->set('respondent_id', 'already-here');
    $response = $service->withRespondentCookie($requestWithCookie, new Response);
    expect($response->headers->getCookies())->toHaveCount(0);
});

test('the respondent cookie is http only', function () {
    $service = new PublicSurveyAccess;

    $response = $service->withRespondentCookie(Request::create('/'), new Response);

    expect($response->headers->getCookies()[0]->isHttpOnly())->toBeTrue();
});

test('duplicate limit is never reached when the policy allows multiple responses', function () {
    $version = SurveyVersion::factory()->create(['settings' => ['duplicate_policy' => DuplicatePolicy::MultipleResponses->value]]);
    SurveyResponse::factory()->create(['survey_version_id' => $version->id, 'respondent_fingerprint' => 'abc']);

    $service = new PublicSurveyAccess;

    expect($service->hasReachedDuplicateLimit($version, 'abc'))->toBeFalse();
});

test('duplicate limit is reached once a complete response exists under one response per user', function () {
    $version = SurveyVersion::factory()->create(['settings' => ['duplicate_policy' => DuplicatePolicy::OneResponsePerUser->value]]);
    SurveyResponse::factory()->create(['survey_version_id' => $version->id, 'respondent_fingerprint' => 'abc']);

    $service = new PublicSurveyAccess;

    expect($service->hasReachedDuplicateLimit($version, 'abc'))->toBeTrue()
        ->and($service->hasReachedDuplicateLimit($version, 'different-fingerprint'))->toBeFalse();
});

test('a partial response does not count toward the one response per user limit', function () {
    $version = SurveyVersion::factory()->create(['settings' => ['duplicate_policy' => DuplicatePolicy::OneResponsePerUser->value]]);
    SurveyResponse::factory()->partial()->create(['survey_version_id' => $version->id, 'respondent_fingerprint' => 'abc']);

    $service = new PublicSurveyAccess;

    expect($service->hasReachedDuplicateLimit($version, 'abc'))->toBeFalse();
});

test('a field agent collected response with no fingerprint never counts toward the duplicate limit', function () {
    $version = SurveyVersion::factory()->create(['settings' => ['duplicate_policy' => DuplicatePolicy::OneResponsePerUser->value]]);
    SurveyResponse::factory()->collectedByFieldAgent()->create(['survey_version_id' => $version->id]);

    $service = new PublicSurveyAccess;

    expect($service->hasReachedDuplicateLimit($version, ''))->toBeFalse();
});

test('a survey has no access grant by default', function () {
    $survey = Survey::factory()->create();
    $service = new PublicSurveyAccess;

    expect($service->hasAccess(requestWithSession(), $survey))->toBeFalse();
});

test('granting access makes hasAccess true for that survey on the same session', function () {
    $survey = Survey::factory()->create();
    $service = new PublicSurveyAccess;
    $request = requestWithSession();

    $service->grantAccess($request, $survey);

    expect($service->hasAccess($request, $survey))->toBeTrue();
});

test('a grant for one survey does not extend to another on the same session', function () {
    $surveyA = Survey::factory()->create();
    $surveyB = Survey::factory()->create();
    $service = new PublicSurveyAccess;
    $request = requestWithSession();

    $service->grantAccess($request, $surveyA);

    expect($service->hasAccess($request, $surveyA))->toBeTrue()
        ->and($service->hasAccess($request, $surveyB))->toBeFalse();
});

test('granting access for a second survey does not clear the grant for the first', function () {
    $surveyA = Survey::factory()->create();
    $surveyB = Survey::factory()->create();
    $service = new PublicSurveyAccess;
    $request = requestWithSession();

    $service->grantAccess($request, $surveyA);
    $service->grantAccess($request, $surveyB);

    expect($service->hasAccess($request, $surveyA))->toBeTrue()
        ->and($service->hasAccess($request, $surveyB))->toBeTrue();
});

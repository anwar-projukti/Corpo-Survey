<?php

use App\Mail\SurveyOtpCode;
use App\Models\ResponseOtp;
use App\Models\SurveyVersion;
use App\Services\SurveyOtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

test('sending an otp creates a hashed code and emails it to the destination', function () {
    $version = SurveyVersion::factory()->create();

    $otp = (new SurveyOtpService)->send($version, 'respondent@example.com');

    expect($otp->destination)->toBe('respondent@example.com')
        ->and($otp->code_hash)->not->toBeNumeric()
        ->and($otp->verified_at)->toBeNull();

    Mail::assertSent(SurveyOtpCode::class, fn ($mail) => $mail->hasTo('respondent@example.com'));
});

test('sending a new otp invalidates any earlier unverified code for the same destination', function () {
    $version = SurveyVersion::factory()->create();
    $service = new SurveyOtpService;

    $first = $service->send($version, 'respondent@example.com');
    $service->send($version, 'respondent@example.com');

    expect(ResponseOtp::find($first->id))->toBeNull()
        ->and(ResponseOtp::where('survey_version_id', $version->id)->where('destination', 'respondent@example.com')->count())->toBe(1);
});

test('a correct code verifies successfully and marks the otp verified', function () {
    $version = SurveyVersion::factory()->create();
    $otp = ResponseOtp::factory()->create([
        'survey_version_id' => $version->id,
        'destination' => 'respondent@example.com',
        'code_hash' => Hash::make('123456'),
    ]);

    $result = (new SurveyOtpService)->verify($version, 'respondent@example.com', '123456');

    expect($result)->toBeTrue()
        ->and($otp->fresh()->verified_at)->not->toBeNull();
});

test('an incorrect code fails verification and increments attempts', function () {
    $version = SurveyVersion::factory()->create();
    $otp = ResponseOtp::factory()->create([
        'survey_version_id' => $version->id,
        'destination' => 'respondent@example.com',
        'code_hash' => Hash::make('123456'),
    ]);

    $result = (new SurveyOtpService)->verify($version, 'respondent@example.com', '000000');

    expect($result)->toBeFalse()
        ->and($otp->fresh()->attempts)->toBe(1)
        ->and($otp->fresh()->verified_at)->toBeNull();
});

test('an expired code fails verification even if correct', function () {
    $version = SurveyVersion::factory()->create();
    ResponseOtp::factory()->expired()->create([
        'survey_version_id' => $version->id,
        'destination' => 'respondent@example.com',
        'code_hash' => Hash::make('123456'),
    ]);

    expect((new SurveyOtpService)->verify($version, 'respondent@example.com', '123456'))->toBeFalse();
});

test('verification is locked out after too many failed attempts even with the correct code', function () {
    $version = SurveyVersion::factory()->create();
    ResponseOtp::factory()->create([
        'survey_version_id' => $version->id,
        'destination' => 'respondent@example.com',
        'code_hash' => Hash::make('123456'),
        'attempts' => 5,
    ]);

    expect((new SurveyOtpService)->verify($version, 'respondent@example.com', '123456'))->toBeFalse();
});

test('isVerified reports true only after a successful verify for that destination', function () {
    $version = SurveyVersion::factory()->create();
    ResponseOtp::factory()->create([
        'survey_version_id' => $version->id,
        'destination' => 'respondent@example.com',
        'code_hash' => Hash::make('123456'),
    ]);
    $service = new SurveyOtpService;

    expect($service->isVerified($version, 'respondent@example.com'))->toBeFalse();

    $service->verify($version, 'respondent@example.com', '123456');

    expect($service->isVerified($version, 'respondent@example.com'))->toBeTrue();
});

test('isVerified is false for a different destination on the same version', function () {
    $version = SurveyVersion::factory()->create();
    ResponseOtp::factory()->verified()->create([
        'survey_version_id' => $version->id,
        'destination' => 'respondent@example.com',
    ]);

    expect((new SurveyOtpService)->isVerified($version, 'someone-else@example.com'))->toBeFalse();
});

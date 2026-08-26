<?php

use App\Actions\Responses\EditResponseAnswer;
use App\Enums\QuestionType;
use App\Enums\ResponseStatus;
use App\Models\ResponseAnswer;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Support\SurveyAnalytics;
use Illuminate\Support\Facades\DB;

function buildVersionWithQuestion(QuestionType $type = QuestionType::ShortText, array $config = []): array
{
    $version = SurveyVersion::factory()->create();
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $question = $section->questions()->create([
        'type' => $type,
        'label' => 'Question',
        'order' => 0,
        'is_required' => false,
        'config' => $config,
    ]);

    return [$version->fresh(['sections.questions']), $question];
}

test('overview reports total, complete, partial, and completion rate', function () {
    [$version] = buildVersionWithQuestion();

    SurveyResponse::factory()->count(3)->create(['survey_version_id' => $version->id, 'status' => ResponseStatus::Complete]);
    SurveyResponse::factory()->partial()->count(1)->create(['survey_version_id' => $version->id]);

    $overview = (new SurveyAnalytics)->overview($version);

    expect($overview['total'])->toBe(4)
        ->and($overview['complete'])->toBe(3)
        ->and($overview['partial'])->toBe(1)
        ->and($overview['completion_rate'])->toBe(75.0);
});

test('overview reports zero completion rate for a version with no responses', function () {
    [$version] = buildVersionWithQuestion();

    $overview = (new SurveyAnalytics)->overview($version);

    expect($overview['total'])->toBe(0)
        ->and($overview['completion_rate'])->toBe(0.0)
        ->and($overview['avg_completion_seconds'])->toBeNull();
});

test('overview computes average completion time from started_at to submitted_at', function () {
    [$version] = buildVersionWithQuestion();

    $started = now()->subMinutes(10);
    SurveyResponse::factory()->create([
        'survey_version_id' => $version->id,
        'status' => ResponseStatus::Complete,
        'started_at' => $started,
        'submitted_at' => $started->copy()->addMinutes(2),
    ]);
    SurveyResponse::factory()->create([
        'survey_version_id' => $version->id,
        'status' => ResponseStatus::Complete,
        'started_at' => $started,
        'submitted_at' => $started->copy()->addMinutes(4),
    ]);

    $overview = (new SurveyAnalytics)->overview($version);

    expect($overview['avg_completion_seconds'])->toBe(180.0);
});

test('overview results are cached and a manual flush recomputes them from a fresh query', function () {
    [$version] = buildVersionWithQuestion();
    SurveyResponse::factory()->create(['survey_version_id' => $version->id]);

    $analytics = new SurveyAnalytics;
    $first = $analytics->overview($version);
    expect($first['total'])->toBe(1);

    // Writes a row directly rather than through the model (bypassing
    // SurveyResponse's own saved() cache-flush hook — see
    // SurveyResponse::booted()) specifically to prove overview() itself
    // is reading from cache rather than recomputing on every call.
    DB::table('survey_responses')->insert([
        'organization_id' => $version->survey->organization_id,
        'survey_id' => $version->survey_id,
        'survey_version_id' => $version->id,
        'status' => 'complete',
        'review_status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $stillCached = $analytics->overview($version);
    expect($stillCached['total'])->toBe(1);

    SurveyAnalytics::flush($version->id);
    $fresh = $analytics->overview($version);
    expect($fresh['total'])->toBe(2);
});

test('creating a response through the model automatically invalidates its versions cached overview', function () {
    [$version] = buildVersionWithQuestion();
    $analytics = new SurveyAnalytics;

    $before = $analytics->overview($version);
    expect($before['total'])->toBe(0);

    SurveyResponse::factory()->create(['survey_version_id' => $version->id]);

    $after = $analytics->overview($version);
    expect($after['total'])->toBe(1);
});

test('editing an answer through EditResponseAnswer invalidates its versions cached question distribution', function () {
    [$version, $question] = buildVersionWithQuestion();
    $response = SurveyResponse::factory()->create(['survey_version_id' => $version->id]);
    $answer = ResponseAnswer::factory()->create(['survey_response_id' => $response->id, 'survey_question_id' => $question->id, 'value' => 'red']);
    $actor = User::factory()->create();

    $analytics = new SurveyAnalytics;
    $before = $analytics->questionDistribution($question->fresh(['section']));
    expect($before)->toBe(['red' => 1]);

    app(EditResponseAnswer::class)->handle($answer, 'blue', $actor);

    $after = $analytics->questionDistribution($question->fresh(['section']));
    expect($after)->toBe(['blue' => 1]);
});

test('trend buckets responses by day', function () {
    [$version] = buildVersionWithQuestion();

    SurveyResponse::factory()->create(['survey_version_id' => $version->id, 'submitted_at' => now()->subDays(2)]);
    SurveyResponse::factory()->create(['survey_version_id' => $version->id, 'submitted_at' => now()->subDays(2)]);
    SurveyResponse::factory()->create(['survey_version_id' => $version->id, 'submitted_at' => now()]);

    $trend = (new SurveyAnalytics)->trend($version, 'day');

    $twoDaysAgo = now()->subDays(2)->toDateString();
    $today = now()->toDateString();

    $byPeriod = collect($trend)->keyBy('period');

    expect($byPeriod[$twoDaysAgo]['count'])->toBe(2)
        ->and($byPeriod[$today]['count'])->toBe(1);
});

test('trend buckets responses by month', function () {
    [$version] = buildVersionWithQuestion();

    SurveyResponse::factory()->create(['survey_version_id' => $version->id, 'submitted_at' => now()->startOfMonth()]);
    SurveyResponse::factory()->create(['survey_version_id' => $version->id, 'submitted_at' => now()->endOfMonth()]);

    $trend = (new SurveyAnalytics)->trend($version, 'month');

    expect($trend)->toHaveCount(1)
        ->and($trend[0]['count'])->toBe(2);
});

test('trend excludes responses with no submitted_at', function () {
    [$version] = buildVersionWithQuestion();

    SurveyResponse::factory()->partial()->create(['survey_version_id' => $version->id, 'submitted_at' => null]);
    SurveyResponse::factory()->create(['survey_version_id' => $version->id, 'submitted_at' => now()]);

    $trend = (new SurveyAnalytics)->trend($version, 'day');

    expect(collect($trend)->sum('count'))->toBe(1);
});

test('drop off reports zero rate for a question every response answered', function () {
    [$version, $question] = buildVersionWithQuestion();

    $responses = SurveyResponse::factory()->count(3)->create(['survey_version_id' => $version->id]);
    foreach ($responses as $response) {
        ResponseAnswer::factory()->create(['survey_response_id' => $response->id, 'survey_question_id' => $question->id, 'value' => 'answered']);
    }

    $dropOff = (new SurveyAnalytics)->dropOff($version);

    expect($dropOff[0]['answered'])->toBe(3)
        ->and($dropOff[0]['drop_off_rate'])->toBe(0.0);
});

test('drop off reports a partial rate when only some responses answered a question', function () {
    [$version, $question] = buildVersionWithQuestion();

    $responses = SurveyResponse::factory()->count(4)->create(['survey_version_id' => $version->id]);
    ResponseAnswer::factory()->create(['survey_response_id' => $responses[0]->id, 'survey_question_id' => $question->id, 'value' => 'answered']);

    $dropOff = (new SurveyAnalytics)->dropOff($version);

    expect($dropOff[0]['answered'])->toBe(1)
        ->and($dropOff[0]['drop_off_rate'])->toBe(75.0);
});

test('drop off returns an empty array when the version has no responses', function () {
    [$version] = buildVersionWithQuestion();

    expect((new SurveyAnalytics)->dropOff($version))->toBe([]);
});

test('question distribution counts each distinct answer value for a single choice question', function () {
    [, $question] = buildVersionWithQuestion(QuestionType::SingleChoice);

    ResponseAnswer::factory()->create(['survey_question_id' => $question->id, 'value' => 'red']);
    ResponseAnswer::factory()->create(['survey_question_id' => $question->id, 'value' => 'red']);
    ResponseAnswer::factory()->create(['survey_question_id' => $question->id, 'value' => 'blue']);

    $distribution = (new SurveyAnalytics)->questionDistribution($question->fresh(['section']));

    expect($distribution)->toBe(['red' => 2, 'blue' => 1]);
});

test('question distribution counts each element separately for a multi select array answer', function () {
    [, $question] = buildVersionWithQuestion(QuestionType::MultipleChoice);

    ResponseAnswer::factory()->create(['survey_question_id' => $question->id, 'value' => ['red', 'blue']]);
    ResponseAnswer::factory()->create(['survey_question_id' => $question->id, 'value' => ['blue']]);

    $distribution = (new SurveyAnalytics)->questionDistribution($question->fresh(['section']));

    expect($distribution)->toBe(['red' => 1, 'blue' => 2]);
});

test('question distribution flattens a matrix answer into row column pairs', function () {
    [, $question] = buildVersionWithQuestion(QuestionType::Matrix, [
        'matrixRows' => ['Speed', 'Support'],
        'matrixColumns' => ['Poor', 'Great'],
    ]);

    ResponseAnswer::factory()->create(['survey_question_id' => $question->id, 'value' => ['Speed' => 'Great', 'Support' => 'Poor']]);

    $distribution = (new SurveyAnalytics)->questionDistribution($question->fresh(['section']));

    expect($distribution)->toBe(['Speed: Great' => 1, 'Support: Poor' => 1]);
});

test('question distribution ignores blank answers', function () {
    [, $question] = buildVersionWithQuestion();

    ResponseAnswer::factory()->create(['survey_question_id' => $question->id, 'value' => 'real answer']);
    ResponseAnswer::factory()->create(['survey_question_id' => $question->id, 'value' => '']);

    $distribution = (new SurveyAnalytics)->questionDistribution($question->fresh(['section']));

    expect($distribution)->toBe(['real answer' => 1]);
});

test('flush clears the overview trend and every question distribution cache for a version', function () {
    [$version, $question] = buildVersionWithQuestion();
    $response = SurveyResponse::factory()->create(['survey_version_id' => $version->id]);
    ResponseAnswer::factory()->create(['survey_response_id' => $response->id, 'survey_question_id' => $question->id, 'value' => 'x']);

    $analytics = new SurveyAnalytics;
    $analytics->overview($version);
    $analytics->trend($version);
    $analytics->questionDistribution($question->fresh(['section']));

    expect(cache()->has(SurveyAnalytics::cacheKey($version->id, 'overview')))->toBeTrue();

    SurveyAnalytics::flush($version->id);

    expect(cache()->has(SurveyAnalytics::cacheKey($version->id, 'overview')))->toBeFalse()
        ->and(cache()->has(SurveyAnalytics::cacheKey($version->id, "question:{$question->id}")))->toBeFalse();
});

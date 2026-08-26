<?php

namespace App\Support;

use App\Enums\QuestionType;
use App\Enums\ResponseStatus;
use App\Models\ResponseAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyVersion;
use Illuminate\Support\Facades\Cache;

/**
 * Computes survey overview metrics and per-question distributions (spec
 * §9) for one survey version at a time — the same version-scoping
 * App\Http\Controllers\Api\V1\Responses\SurveyResponseController already
 * uses, since each version can have a different question set.
 *
 * Every computation here is cached (spec §9/§14 — "cached analytics,
 * don't recompute on every dashboard load") and invalidated the moment a
 * response or answer is created, edited, deleted, or restored — see
 * App\Models\SurveyResponse::booted() and App\Models\ResponseAnswer::booted()
 * — rather than on a fixed TTL, so the dashboard is never more than one
 * write behind reality.
 */
class SurveyAnalytics
{
    private const CACHE_TTL_SECONDS = 3600;

    public static function cacheKey(int $versionId, string $suffix): string
    {
        return "survey-analytics:{$versionId}:{$suffix}";
    }

    public static function flush(int $versionId): void
    {
        Cache::forget(self::cacheKey($versionId, 'overview'));

        foreach (['day', 'week', 'month'] as $granularity) {
            Cache::forget(self::cacheKey($versionId, "trend:{$granularity}"));
        }

        SurveyQuestion::query()
            ->whereHas('section', fn ($q) => $q->where('survey_version_id', $versionId))
            ->pluck('id')
            ->each(fn ($questionId) => Cache::forget(self::cacheKey($versionId, "question:{$questionId}")));
    }

    /**
     * @return array{total: int, complete: int, partial: int, completion_rate: float, avg_completion_seconds: ?float}
     */
    public function overview(SurveyVersion $version): array
    {
        return Cache::remember(self::cacheKey($version->id, 'overview'), self::CACHE_TTL_SECONDS, function () use ($version) {
            $base = $version->responses();

            $total = (clone $base)->count();
            $complete = (clone $base)->where('status', ResponseStatus::Complete)->count();
            $partial = $total - $complete;

            $avgSeconds = (clone $base)
                ->where('status', ResponseStatus::Complete)
                ->whereNotNull('started_at')
                ->whereNotNull('submitted_at')
                ->get(['started_at', 'submitted_at'])
                // diffInSeconds()'s sign depends on which timestamp calls
                // it (it's $arg - $this, not $this - $arg) — abs() sidesteps
                // needing to remember that convention correctly here.
                ->map(fn ($r) => abs($r->submitted_at->diffInSeconds($r->started_at)))
                ->avg();

            return [
                'total' => $total,
                'complete' => $complete,
                'partial' => $partial,
                'completion_rate' => $total > 0 ? round($complete / $total * 100, 1) : 0.0,
                'avg_completion_seconds' => $avgSeconds !== null ? round($avgSeconds, 1) : null,
            ];
        });
    }

    /**
     * Response counts bucketed by day/week/month (spec §9's "response
     * trend"), from each response's submitted_at. Bucketed in PHP rather
     * than with a SQL date-format expression: MySQL's DATE_FORMAT and
     * SQLite's strftime don't share compatible format codes for
     * week-of-year (ResponseFilterEngine already hit a similar SQLite/
     * MySQL divergence over JSON functions), and a survey's response
     * volume is nowhere near large enough for pulling raw timestamps to
     * matter for performance.
     *
     * @return array<int, array{period: string, count: int}>
     */
    public function trend(SurveyVersion $version, string $granularity = 'day'): array
    {
        $cacheKey = self::cacheKey($version->id, "trend:{$granularity}");

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($version, $granularity) {
            $timestamps = $version->responses()
                ->whereNotNull('submitted_at')
                ->pluck('submitted_at');

            $buckets = [];

            foreach ($timestamps as $timestamp) {
                $period = match ($granularity) {
                    'week' => $timestamp->startOfWeek()->toDateString(),
                    'month' => $timestamp->format('Y-m'),
                    default => $timestamp->toDateString(),
                };

                $buckets[$period] = ($buckets[$period] ?? 0) + 1;
            }

            ksort($buckets);

            return collect($buckets)
                ->map(fn ($count, $period) => ['period' => $period, 'count' => $count])
                ->values()
                ->all();
        });
    }

    /**
     * Per-question drop-off: how many respondents who started this
     * version's response never reached (answered) each question, in
     * section/question order — surfaces where people are abandoning the
     * survey.
     *
     * @return array<int, array{question_id: int, label: string, answered: int, drop_off_rate: float}>
     */
    public function dropOff(SurveyVersion $version): array
    {
        $totalStarted = $version->responses()->count();

        if ($totalStarted === 0) {
            return [];
        }

        $questions = $version->sections->flatMap->questions;

        $answeredCounts = ResponseAnswer::query()
            ->whereIn('survey_question_id', $questions->pluck('id'))
            ->whereNotNull('value')
            ->where('value', '!=', json_encode(''))
            ->selectRaw('survey_question_id, count(*) as count')
            ->groupBy('survey_question_id')
            ->pluck('count', 'survey_question_id');

        return $questions->map(function (SurveyQuestion $question) use ($answeredCounts, $totalStarted) {
            $answered = (int) ($answeredCounts[$question->id] ?? 0);

            return [
                'question_id' => $question->id,
                'label' => $question->label,
                'answered' => $answered,
                'drop_off_rate' => round((1 - $answered / $totalStarted) * 100, 1),
            ];
        })->values()->all();
    }

    /**
     * The answer-value distribution for one question — a count per
     * distinct value (choice questions, yes/no, rating scales, NPS,
     * Likert) or per-row/column pair for a matrix. Not meaningful for
     * free-text types (short_text/long_text/email/...), which the
     * frontend is expected to skip rendering a chart for.
     *
     * @return array<string, int>
     */
    public function questionDistribution(SurveyQuestion $question): array
    {
        return Cache::remember(
            self::cacheKey($question->section->survey_version_id, "question:{$question->id}"),
            self::CACHE_TTL_SECONDS,
            function () use ($question) {
                $values = ResponseAnswer::query()
                    ->where('survey_question_id', $question->id)
                    ->whereNotNull('value')
                    ->pluck('value');

                $counts = [];

                foreach ($values as $value) {
                    foreach ($this->flattenAnswerValue($question->type, $value) as $label) {
                        $counts[$label] = ($counts[$label] ?? 0) + 1;
                    }
                }

                return $counts;
            }
        );
    }

    /**
     * A single response value can represent one label (a rating), several
     * (a multi-select array), or a row=>column pair per matrix row —
     * normalized here to a flat list of "bucket" labels to tally, so
     * questionDistribution() doesn't need type-specific counting logic.
     *
     * @return array<int, string>
     */
    private function flattenAnswerValue(QuestionType $type, mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        if ($type === QuestionType::Matrix && is_array($value)) {
            return array_map(fn ($row, $column) => "$row: $column", array_keys($value), array_values($value));
        }

        if (is_array($value)) {
            return array_map(strval(...), $value);
        }

        return [(string) $value];
    }
}

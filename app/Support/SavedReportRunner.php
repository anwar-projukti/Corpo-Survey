<?php

namespace App\Support;

use App\Enums\QuestionType;
use App\Models\SavedReport;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Executes a saved report's configuration (spec §9's custom report
 * builder) against live data: applies its App\Support\ResponseFilterEngine
 * filters and optional submitted_at date range, then computes a
 * per-question answer distribution for just the questions the report
 * selected.
 *
 * Deliberately uncached, unlike App\Support\SurveyAnalytics — a saved
 * report's filters/date-range are arbitrary per report, so there's no
 * single cache key worth invalidating on every write the way the
 * dashboard's fixed overview/trend/per-question keys are, and reports are
 * opened far less often than the live dashboard.
 */
class SavedReportRunner
{
    /**
     * @return array{response_count: int, questions: array<int, array{question_id: int, label: string, type: string, chart_type: string, distribution: array<string, int>}>}
     */
    public function run(SavedReport $report): array
    {
        $config = $report->config;
        $version = $report->survey->currentPublishedVersion ?? $report->survey->currentDraftVersion;

        if ($version === null) {
            return ['response_count' => 0, 'questions' => []];
        }

        $questionIds = $config['question_ids'] ?? [];
        $filters = $config['filters'] ?? [];
        $dateFrom = $config['date_from'] ?? null;
        $dateTo = $config['date_to'] ?? null;

        $responseQuery = SurveyResponse::query()->where('survey_version_id', $version->id);

        if ($dateFrom !== null) {
            $responseQuery->where('submitted_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        if ($dateTo !== null) {
            $responseQuery->where('submitted_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        if ($filters !== []) {
            ResponseFilterEngine::apply($responseQuery, $filters);
        }

        $responseIds = $responseQuery->pluck('id');

        $questions = SurveyQuestion::query()
            ->whereIn('id', $questionIds)
            ->whereHas('section', fn ($q) => $q->where('survey_version_id', $version->id))
            ->with('section')
            ->get()
            ->sortBy(fn ($question) => array_search($question->id, $questionIds, true));

        return [
            'response_count' => $responseIds->count(),
            'questions' => $questions->map(function (SurveyQuestion $question) use ($responseIds, $config) {
                return [
                    'question_id' => $question->id,
                    'label' => $question->label,
                    'type' => $question->type->value,
                    'chart_type' => $config['chart_types'][$question->id] ?? $this->defaultChartType($question->type),
                    'distribution' => $this->distributionFor($question, $responseIds),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, int>  $responseIds
     * @return array<string, int>
     */
    private function distributionFor(SurveyQuestion $question, $responseIds): array
    {
        $values = $question->answers()
            ->whereIn('survey_response_id', $responseIds)
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

    /**
     * Mirrors App\Support\SurveyAnalytics::flattenAnswerValue() — kept as
     * its own copy rather than a shared extraction, since the two classes'
     * query shapes (a single question vs. a whole version) differ enough
     * that a shared helper would need to take the flattening logic as its
     * only common parameter anyway.
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

    private function defaultChartType(QuestionType $type): string
    {
        return match ($type) {
            QuestionType::Nps, QuestionType::NumericRating, QuestionType::Star, QuestionType::Slider, QuestionType::Range => 'bar',
            default => 'doughnut',
        };
    }
}

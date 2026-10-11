<?php

namespace App\Support;

use App\Enums\QuestionType;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use Illuminate\Support\Collection;

/**
 * Turns a version's questions into Excel columns and a response into one
 * row of values (spec §10.1) — survey questions become columns
 * automatically, no manual mapping needed for a standard export. Shared by
 * App\Exports\SurveyResponsesSheet (single survey) and
 * App\Exports\OrganizationExport (one sheet per survey) so both produce
 * identical column shapes for the same survey.
 */
class ResponseExportRowBuilder
{
    /**
     * Optional columns an admin can toggle on top of the always-included
     * question columns (spec §10.1's "respondent info, response ID,
     * submission date, completion time, IP, location, status").
     *
     * @var list<string>
     */
    public const OPTIONAL_COLUMNS = [
        'response_id', 'respondent_name', 'respondent_email', 'respondent_phone',
        'status', 'submitted_at', 'completion_time_seconds', 'ip_address', 'collector_name', 'location',
    ];

    /**
     * @return Collection<int, SurveyQuestion>
     */
    public function questionsFor(SurveyVersion $version): Collection
    {
        return $version->sections()
            ->with('questions')
            ->get()
            ->flatMap->questions
            ->values();
    }

    /**
     * @param  list<string>  $optionalColumns
     * @return list<string>
     */
    public function headingsFor(Collection $questions, array $optionalColumns): array
    {
        $headings = [];

        foreach ($optionalColumns as $column) {
            $headings[] = match ($column) {
                'response_id' => 'Response ID',
                'respondent_name' => 'Name',
                'respondent_email' => 'Email',
                'respondent_phone' => 'Phone',
                'status' => 'Status',
                'submitted_at' => 'Submitted At',
                'completion_time_seconds' => 'Completion Time (s)',
                'ip_address' => 'IP Address',
                'collector_name' => 'Collected By',
                'location' => 'Location',
                default => $column,
            };
        }

        foreach ($questions as $question) {
            $headings[] = $question->label;
        }

        return $headings;
    }

    /**
     * @param  Collection<int, SurveyQuestion>  $questions
     * @param  list<string>  $optionalColumns
     * @return list<mixed>
     */
    public function rowFor(SurveyResponse $response, Collection $questions, array $optionalColumns): array
    {
        $answersByQuestion = $response->answers->keyBy('survey_question_id');

        $row = [];

        foreach ($optionalColumns as $column) {
            $row[] = match ($column) {
                'response_id' => $response->id,
                'respondent_name' => $response->respondent?->name,
                'respondent_email' => $response->respondent?->email,
                'respondent_phone' => $response->respondent?->phone,
                'status' => $response->status->value,
                'submitted_at' => $response->submitted_at?->toDateTimeString(),
                'completion_time_seconds' => $this->completionTimeSeconds($response),
                'ip_address' => $response->meta['ip_address'] ?? null,
                'collector_name' => $response->collector?->name,
                'location' => $response->latitude !== null && $response->longitude !== null
                    ? "{$response->latitude},{$response->longitude}"
                    : null,
                default => null,
            };
        }

        foreach ($questions as $question) {
            $answer = $answersByQuestion->get($question->id);
            $row[] = $answer ? $this->formatAnswerValue($question->type, $answer->value) : null;
        }

        return $row;
    }

    private function completionTimeSeconds(SurveyResponse $response): ?int
    {
        if ($response->started_at === null || $response->submitted_at === null) {
            return null;
        }

        return $response->submitted_at->diffInSeconds($response->started_at);
    }

    private function formatAnswerValue(QuestionType $type, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($type === QuestionType::Matrix && is_array($value)) {
            return collect($value)
                ->map(fn ($column, $row) => "$row: $column")
                ->implode('; ');
        }

        if (is_array($value)) {
            return implode(', ', array_map(strval(...), $value));
        }

        return (string) $value;
    }
}

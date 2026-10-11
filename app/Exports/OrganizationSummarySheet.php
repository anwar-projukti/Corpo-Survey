<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\UserSurveyStat;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * The org-wide export's summary sheet (spec §10.2): survey name, total
 * responses, completion rate, date range, and a breakdown by field user —
 * one row per survey, listed before the per-survey sheets.
 */
class OrganizationSummarySheet implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  Collection<int, Survey>  $surveys
     */
    public function __construct(private readonly Organization $organization, private readonly Collection $surveys) {}

    public function headings(): array
    {
        return ['Survey', 'Total Responses', 'Completed', 'Completion Rate', 'First Response', 'Last Response', 'Field Users Involved'];
    }

    public function array(): array
    {
        return $this->surveys->map(function (Survey $survey) {
            $responses = SurveyResponse::query()->where('survey_id', $survey->id);
            $total = (clone $responses)->count();
            $completed = (clone $responses)->where('status', 'complete')->count();

            $fieldUserCount = UserSurveyStat::query()
                ->where('survey_id', $survey->id)
                ->distinct('user_id')
                ->count('user_id');

            return [
                $survey->title,
                $total,
                $completed,
                $total > 0 ? round($completed / $total * 100, 1).'%' : '0%',
                (clone $responses)->min('submitted_at'),
                (clone $responses)->max('submitted_at'),
                $fieldUserCount,
            ];
        })->all();
    }

    public function title(): string
    {
        return 'Summary';
    }
}

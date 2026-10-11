<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The org-wide export (spec §10.2): a summary sheet plus one sheet per
 * survey, each built from App\Exports\SurveyResponsesSheet so the columns
 * are identical to what a single-survey export would produce for the same
 * survey. Only surveys that have at least one response and a published (or
 * else draft) version are included — an empty survey would just be a
 * headings-only sheet with no question columns to speak of.
 */
class OrganizationExport implements Export, WithMultipleSheets
{
    /**
     * @var list<string>
     */
    private const DEFAULT_OPTIONAL_COLUMNS = ['response_id', 'respondent_name', 'respondent_email', 'status', 'submitted_at'];

    public function __construct(private readonly Organization $organization) {}

    public function sheets(): array
    {
        $surveys = Survey::query()
            ->where('organization_id', $this->organization->id)
            ->whereHas('responses')
            ->with(['currentPublishedVersion', 'currentDraftVersion'])
            ->get();

        $sheets = [new OrganizationSummarySheet($this->organization, $surveys)];

        foreach ($surveys as $survey) {
            $version = $survey->currentPublishedVersion ?? $survey->currentDraftVersion;

            if ($version === null) {
                continue;
            }

            $query = SurveyResponse::query()->where('survey_version_id', $version->id);

            $sheets[] = new SurveyResponsesSheet($version, $query, self::DEFAULT_OPTIONAL_COLUMNS, $survey->title);
        }

        return $sheets;
    }
}

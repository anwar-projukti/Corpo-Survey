<?php

namespace App\Exports;

use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Support\ResponseExportRowBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * One survey's responses as a single sheet (spec §10.1/§10.2) — question
 * columns are generated dynamically from the version's questions, never
 * fixed columns. Reused as-is for both the single-survey export
 * (App\Http\Controllers\Api\V1\Exports\SurveyExportController) and as one
 * sheet per survey inside the org-wide export (App\Exports\OrganizationExport).
 */
class SurveyResponsesSheet implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    private ResponseExportRowBuilder $rowBuilder;

    private Collection $questions;

    /**
     * @param  EloquentBuilder<SurveyResponse>|Relation<SurveyResponse, SurveyResponse, Collection<int, SurveyResponse>>  $query
     * @param  list<string>  $optionalColumns
     */
    public function __construct(
        private readonly SurveyVersion $version,
        private readonly EloquentBuilder|Relation $query,
        private readonly array $optionalColumns,
        private readonly string $title,
    ) {
        $this->rowBuilder = new ResponseExportRowBuilder;
        $this->questions = $this->rowBuilder->questionsFor($version);
    }

    /**
     * Ordered by id — FromQuery's chunked pagination needs a deterministic,
     * unique ORDER BY tie-breaker (see the interface's own docblock) or
     * rows can be skipped/duplicated across chunk boundaries.
     */
    public function query(): Builder|EloquentBuilder|Relation
    {
        return $this->query->with(['answers', 'respondent', 'collector'])->orderBy('id');
    }

    public function headings(): array
    {
        return $this->rowBuilder->headingsFor($this->questions, $this->optionalColumns);
    }

    public function map($response): array
    {
        return $this->rowBuilder->rowFor($response, $this->questions, $this->optionalColumns);
    }

    public function title(): string
    {
        // Excel sheet names are capped at 31 characters and can't contain
        // \ / ? * [ ] — truncate and strip rather than letting the writer
        // throw on a long/rich survey title.
        return substr(preg_replace('/[\\\\\/\?\*\[\]]/', '', $this->title), 0, 31);
    }
}

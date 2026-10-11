<?php

namespace App\Jobs;

use App\Exports\SurveyResponsesSheet;
use App\Models\ExportJob;
use App\Notifications\ExportReadyNotification;
use App\Services\AuditLogger;
use App\Support\ResponseFilterEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Builds a single survey's export file (spec §10.1) off the request's
 * chosen scope (all/filtered/selected) and columns, then notifies whoever
 * requested it. Always queued, even for a small survey — the spec's rule
 * (§10.4) is "large exports/imports run as queued jobs"; keeping every
 * export on the same code path avoids a second, synchronous variant that
 * would need to stay in sync with this one.
 */
class GenerateSurveyExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly ExportJob $exportJob) {}

    public function handle(AuditLogger $auditLogger): void
    {
        $exportJob = $this->exportJob;
        $options = $exportJob->options ?? [];

        try {
            $survey = $exportJob->survey()->withoutGlobalScopes()->first();
            $version = $survey->versions()->find($options['version_id'] ?? null)
                ?? $survey->currentPublishedVersion
                ?? $survey->currentDraftVersion;

            if ($version === null) {
                throw new \RuntimeException('Survey has no version to export.');
            }

            $query = $version->responses();

            match ($options['scope'] ?? 'all') {
                'selected' => $query->whereIn('id', $options['response_ids'] ?? []),
                'filtered' => ResponseFilterEngine::apply($query, $options['filters'] ?? []),
                default => null,
            };

            $rowCount = (clone $query)->count();
            $optionalColumns = $options['columns'] ?? ['response_id', 'respondent_name', 'respondent_email', 'status', 'submitted_at'];

            $sheet = new SurveyResponsesSheet($version, $query, $optionalColumns, $survey->title);

            $extension = $exportJob->format === 'csv' ? 'csv' : 'xlsx';
            $writerType = $exportJob->format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
            $relativePath = "exports/{$exportJob->organization_id}/".Str::uuid().".{$extension}";

            Excel::store($sheet, $relativePath, 'local', $writerType);

            $exportJob->markCompleted($relativePath, $rowCount);

            $auditLogger->log('response.exported', subject: $survey, properties: [
                'export_job_id' => $exportJob->id,
                'scope' => $options['scope'] ?? 'all',
                'row_count' => $rowCount,
            ], actor: $exportJob->requester);

            $exportJob->requester?->notify(new ExportReadyNotification($exportJob));
        } catch (\Throwable $e) {
            $exportJob->markFailed($e->getMessage());

            throw $e;
        }
    }
}

<?php

namespace App\Jobs;

use App\Exports\OrganizationExport;
use App\Models\ExportJob;
use App\Models\SurveyResponse;
use App\Notifications\ExportReadyNotification;
use App\Services\AuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Builds the org-wide export (spec §10.2): one sheet per survey plus a
 * summary sheet, via App\Exports\OrganizationExport.
 */
class GenerateOrganizationExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly ExportJob $exportJob) {}

    public function handle(AuditLogger $auditLogger): void
    {
        $exportJob = $this->exportJob;

        try {
            $organization = $exportJob->organization;

            $rowCount = SurveyResponse::query()
                ->withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->count();

            $export = new OrganizationExport($organization);

            $extension = $exportJob->format === 'csv' ? 'csv' : 'xlsx';
            $writerType = $exportJob->format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
            $relativePath = "exports/{$organization->id}/".Str::uuid().".{$extension}";

            Excel::store($export, $relativePath, 'local', $writerType);

            $exportJob->markCompleted($relativePath, $rowCount);

            $auditLogger->log('organization.exported', subject: $organization, properties: [
                'export_job_id' => $exportJob->id,
                'row_count' => $rowCount,
            ], actor: $exportJob->requester);

            $exportJob->requester?->notify(new ExportReadyNotification($exportJob));
        } catch (\Throwable $e) {
            $exportJob->markFailed($e->getMessage());

            throw $e;
        }
    }
}

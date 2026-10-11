<?php

namespace App\Http\Controllers\Api\V1\Exports;

use App\Http\Controllers\Controller;
use App\Http\Resources\Exports\ExportJobResource;
use App\Models\ExportJob;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Export history (spec §13's "Export History" nav item) plus the signed
 * download endpoint itself.
 */
class ExportJobController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('export', SurveyResponse::class);

        $perPage = min((int) $request->integer('per_page', 20), 100);

        $exportJobs = ExportJob::query()
            ->with(['requester', 'survey'])
            ->latest()
            ->paginate($perPage);

        return ExportJobResource::collection($exportJobs);
    }

    /**
     * Reached only via a signed URL (spec §10.4) — the "signed" route
     * middleware rejects a tampered or expired link before this method
     * ever runs, so no separate ability check is needed here beyond that
     * signature (the ability was already enforced when the job was
     * created). The file itself lives on the "local" disk, which is never
     * web-served (config/filesystems.php), so this download is the only
     * way to retrieve it.
     */
    public function download(ExportJob $exportJob): Response
    {
        abort_unless($exportJob->status === 'completed' && $exportJob->file_path !== null, 404);
        abort_unless(Storage::disk('local')->exists($exportJob->file_path), 404);

        $extension = $exportJob->format === 'csv' ? 'csv' : 'xlsx';
        $filename = "export-{$exportJob->id}.{$extension}";

        return Storage::disk('local')->download($exportJob->file_path, $filename);
    }
}

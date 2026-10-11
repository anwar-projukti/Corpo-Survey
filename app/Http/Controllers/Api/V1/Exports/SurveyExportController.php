<?php

namespace App\Http\Controllers\Api\V1\Exports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exports\StoreSurveyExportRequest;
use App\Http\Resources\Exports\ExportJobResource;
use App\Jobs\GenerateSurveyExportJob;
use App\Models\ExportJob;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\JsonResponse;

/**
 * Single-survey export (spec §10.1/§10.2) — always queued (spec §10.4),
 * the admin is handed back a job to poll and a signed download link once
 * it completes (App\Notifications\ExportReadyNotification).
 */
class SurveyExportController extends Controller
{
    public function store(StoreSurveyExportRequest $request, Survey $survey): JsonResponse
    {
        $this->authorize('view', $survey);
        $this->authorize('export', SurveyResponse::class);

        $exportJob = ExportJob::create([
            'survey_id' => $survey->id,
            'requested_by' => $request->user()->id,
            'type' => 'survey',
            'format' => $request->string('format')->value(),
            'status' => 'pending',
            'options' => $request->only(['scope', 'version_id', 'response_ids', 'filters', 'columns']),
        ]);

        GenerateSurveyExportJob::dispatch($exportJob);

        return (new ExportJobResource($exportJob))->response()->setStatusCode(202);
    }
}

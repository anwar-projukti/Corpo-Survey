<?php

namespace App\Http\Controllers\Api\V1\Exports;

use App\Http\Controllers\Controller;
use App\Http\Resources\Exports\ExportJobResource;
use App\Jobs\GenerateOrganizationExportJob;
use App\Models\ExportJob;
use App\Models\Organization;
use App\Models\SurveyResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Org-wide export (spec §10.2) — one sheet per survey plus a summary
 * sheet. $organization is always the requesting user's own org; there is
 * no cross-org export, so the route parameter exists only to make the
 * endpoint shape match spec §10.2 rather than to allow choosing another org.
 */
class OrganizationExportController extends Controller
{
    public function store(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('export', SurveyResponse::class);
        abort_unless($organization->id === $request->user()->organization_id, 403);

        $data = $request->validate([
            'format' => ['required', 'string', Rule::in(['xlsx', 'csv'])],
        ]);

        $exportJob = ExportJob::create([
            'requested_by' => $request->user()->id,
            'type' => 'organization',
            'format' => $data['format'],
            'status' => 'pending',
        ]);

        GenerateOrganizationExportJob::dispatch($exportJob);

        return (new ExportJobResource($exportJob))->response()->setStatusCode(202);
    }
}

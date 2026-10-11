<?php

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\StoreSavedReportRequest;
use App\Http\Requests\Analytics\UpdateSavedReportRequest;
use App\Http\Resources\Analytics\SavedReportResource;
use App\Http\Resources\Analytics\SavedReportRunResource;
use App\Models\SavedReport;
use App\Models\Survey;
use App\Support\SavedReportRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Custom report builder persistence (spec §9) — save a report's selected
 * questions, App\Support\ResponseFilterEngine filters, per-question chart
 * types, and date range for reuse, without needing to rebuild it from
 * scratch every visit. Real file export (Excel/CSV/PDF) is Phase 9's
 * concern, once Maatwebsite Excel is installed; running a saved report
 * here always returns live computed data, never a file.
 */
class SavedReportController extends Controller
{
    public function index(Survey $survey): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [SavedReport::class, $survey]);

        return SavedReportResource::collection(
            $survey->savedReports()->latest()->get()
        );
    }

    public function store(StoreSavedReportRequest $request, Survey $survey): SavedReportResource
    {
        $report = $survey->savedReports()->create([
            'created_by' => $request->user()->id,
            'name' => $request->string('name')->value(),
            'config' => $request->input('config'),
        ]);

        return new SavedReportResource($report);
    }

    public function update(UpdateSavedReportRequest $request, SavedReport $savedReport): SavedReportResource
    {
        $savedReport->update([
            'name' => $request->string('name')->value(),
            'config' => $request->input('config'),
        ]);

        return new SavedReportResource($savedReport);
    }

    public function destroy(SavedReport $savedReport): JsonResponse
    {
        $this->authorize('delete', $savedReport);

        $savedReport->delete();

        return response()->json(['success' => true, 'message' => 'Report deleted.']);
    }

    public function run(SavedReport $savedReport, SavedReportRunner $runner): SavedReportRunResource
    {
        $this->authorize('view', $savedReport);

        return new SavedReportRunResource($runner->run($savedReport));
    }
}

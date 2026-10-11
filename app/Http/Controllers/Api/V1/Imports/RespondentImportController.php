<?php

namespace App\Http\Controllers\Api\V1\Imports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Imports\ConfirmRespondentImportRequest;
use App\Http\Requests\Imports\StoreRespondentImportRequest;
use App\Http\Resources\Imports\ImportJobResource;
use App\Jobs\ImportRespondentsJob;
use App\Models\ImportJob;
use App\Models\SurveyResponse;
use App\Support\RawSpreadsheetReader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Excel import (spec §10.4): upload -> preview (detected columns) ->
 * column mapping -> validation/duplicate detection/summary (queued,
 * App\Jobs\ImportRespondentsJob) -> import history. store() and confirm()
 * are the two admin-facing steps; the heavy row-by-row work only ever
 * happens after confirm(), so a bad mapping never touches the database.
 */
class RespondentImportController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('import', SurveyResponse::class);

        $perPage = min((int) $request->integer('per_page', 20), 100);

        $imports = ImportJob::query()->with('requester')->latest()->paginate($perPage);

        return ImportJobResource::collection($imports);
    }

    public function store(StoreRespondentImportRequest $request): ImportJobResource
    {
        $this->authorize('import', SurveyResponse::class);

        $file = $request->file('file');

        // Randomized filename, stored outside any publicly-served
        // directory (spec §5.9) — never the client-supplied filename,
        // which is untrusted input.
        $relativePath = 'imports/'.$request->user()->organization_id.'/'.Str::uuid().'.'.$file->extension();
        $file->storeAs('', $relativePath, 'local');

        $sheets = Excel::toCollection(new RawSpreadsheetReader, $file);
        $headings = $sheets->first()?->first()?->map(fn ($value) => (string) $value)->all() ?? [];

        $importJob = ImportJob::create([
            'requested_by' => $request->user()->id,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
            'status' => 'uploaded',
            'detected_columns' => $headings,
        ]);

        return new ImportJobResource($importJob);
    }

    public function show(ImportJob $importJob): ImportJobResource
    {
        $this->authorize('import', SurveyResponse::class);

        return new ImportJobResource($importJob->load('requester'));
    }

    public function confirm(ConfirmRespondentImportRequest $request, ImportJob $importJob): ImportJobResource
    {
        abort_unless($importJob->status === 'uploaded', 422, 'This import has already been confirmed.');

        $importJob->markMappingConfirmed($request->validated('column_mapping'));

        ImportRespondentsJob::dispatch($importJob);

        return new ImportJobResource($importJob);
    }
}

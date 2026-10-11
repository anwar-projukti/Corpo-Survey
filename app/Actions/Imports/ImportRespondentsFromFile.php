<?php

namespace App\Actions\Imports;

use App\Models\ImportJob;
use App\Models\Respondent;
use App\Services\AuditLogger;
use App\Support\RawSpreadsheetReader;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Runs the actual row-by-row import (spec §10.4's validation/duplicate
 * detection/summary steps) once an admin has confirmed a column mapping.
 * A plain Action rather than living inline in the queued job, so the same
 * logic is unit-testable without dispatching a real job.
 *
 * Duplicate detection matches on email OR phone within the organization —
 * whichever of the two the admin actually mapped a column to. Respondent
 * fields are 'encrypted' casts (see App\Models\Respondent), so matching
 * happens by decrypting each existing row's value against the plaintext
 * import value rather than a WHERE clause against the ciphertext.
 */
class ImportRespondentsFromFile
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(ImportJob $importJob): void
    {
        $mapping = $importJob->column_mapping;
        $sheet = Excel::toCollection(new RawSpreadsheetReader, Storage::disk('local')->path($importJob->file_path))->first() ?? collect();
        $rows = $sheet->map(fn ($row) => $row->all())->all();
        $headings = array_map('strval', array_shift($rows) ?? []);

        $existing = Respondent::query()
            ->where('organization_id', $importJob->organization_id)
            ->get(['id', 'email', 'phone']);

        $imported = 0;
        $duplicates = 0;
        $failed = 0;
        $rowErrors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 for zero-index, +1 for the header row already shifted off

            if ($this->isBlankRow($row)) {
                continue;
            }

            $values = $this->mapRow($headings, $row, $mapping);

            if (($values['name'] ?? null) === null || trim((string) $values['name']) === '') {
                $failed++;
                $rowErrors[] = ['row' => $rowNumber, 'error' => 'Missing required name value.'];

                continue;
            }

            $duplicate = $existing->first(fn ($respondent) => ($values['email'] && $respondent->email === $values['email'])
                || ($values['phone'] && $respondent->phone === $values['phone']));

            if ($duplicate !== null) {
                $duplicates++;

                continue;
            }

            // organization_id isn't mass-assignable (see Respondent's
            // $fillable) and BelongsToOrganization's creating() hook only
            // stamps it from Auth::user() — but this action runs inside a
            // queued job (App\Jobs\ImportRespondentsJob) with no
            // authenticated user in that context, so the hook would
            // otherwise leave it null. forceFill it explicitly instead.
            $respondent = new Respondent([
                'name' => $values['name'],
                'email' => $values['email'] ?? null,
                'phone' => $values['phone'] ?? null,
            ]);
            $respondent->forceFill(['organization_id' => $importJob->organization_id])->save();

            $existing->push($respondent);
            $imported++;
        }

        $importJob->markCompleted(count($rows), $imported, $duplicates, $failed, $rowErrors);

        $this->auditLogger->log('respondents.imported', subject: $importJob, properties: [
            'imported' => $imported,
            'duplicates' => $duplicates,
            'failed' => $failed,
        ], actor: $importJob->requester);
    }

    /**
     * @param  list<string>  $headings
     * @param  list<mixed>  $row
     * @param  array<string, string|null>  $mapping
     * @return array<string, string|null>
     */
    private function mapRow(array $headings, array $row, array $mapping): array
    {
        $values = [];

        foreach (['name', 'email', 'phone'] as $field) {
            $column = $mapping[$field] ?? null;
            $columnIndex = $column !== null ? array_search($column, $headings, true) : false;
            $values[$field] = $columnIndex !== false ? trim((string) ($row[$columnIndex] ?? '')) ?: null : null;
        }

        return $values;
    }

    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => $value === null || trim((string) $value) === '');
    }
}

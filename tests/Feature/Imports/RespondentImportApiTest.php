<?php

use App\Jobs\ImportRespondentsJob;
use App\Models\ImportJob;
use App\Models\Organization;
use App\Models\Respondent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

function orgAdminForImports(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    return [$organization, $admin];
}

/**
 * Maatwebsite needs a real, parseable spreadsheet — UploadedFile::fake()
 * only produces junk bytes with a plausible extension, which fails to
 * parse as an actual xlsx. This writes a real one to a temp path and wraps
 * it as an UploadedFile the same way a real HTTP upload would arrive.
 */
function fakeRespondentSpreadsheet(array $rows): UploadedFile
{
    $export = new class($rows) implements FromArray, WithHeadings
    {
        public function __construct(private array $rows) {}

        public function array(): array
        {
            return $this->rows;
        }

        public function headings(): array
        {
            return ['Full Name', 'Email Address', 'Phone Number'];
        }
    };

    $path = tempnam(sys_get_temp_dir(), 'import').'.xlsx';
    Excel::store($export, basename($path), 'local', Maatwebsite\Excel\Excel::XLSX);
    $storedPath = storage_path('app/private/'.basename($path));

    return new UploadedFile($storedPath, 'respondents.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('an Org Admin can upload a respondent file and see detected columns', function () {
    [, $admin] = orgAdminForImports();
    $file = fakeRespondentSpreadsheet([['Jane Doe', 'jane@example.com', '01700000000']]);

    $result = $this->actingAs($admin)->postJson('/api/v1/imports', ['file' => $file]);

    $result->assertCreated()->assertJsonPath('data.status', 'uploaded');
    expect($result->json('data.detected_columns'))->toContain('Full Name', 'Email Address', 'Phone Number');
});

test('a non-spreadsheet upload is rejected', function () {
    [, $admin] = orgAdminForImports();
    $file = UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload');

    $this->actingAs($admin)->postJson('/api/v1/imports', ['file' => $file])
        ->assertUnprocessable()->assertJsonValidationErrors(['file']);
});

test('a Field User cannot upload an import file', function () {
    [$organization] = orgAdminForImports();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $file = fakeRespondentSpreadsheet([['Jane Doe', 'jane@example.com', '01700000000']]);

    $this->actingAs($fieldUser)->postJson('/api/v1/imports', ['file' => $file])->assertForbidden();
});

test('confirming the column mapping dispatches the import job', function () {
    Queue::fake();
    [$organization, $admin] = orgAdminForImports();
    $importJob = ImportJob::factory()->create(['organization_id' => $organization->id, 'status' => 'uploaded']);

    $result = $this->actingAs($admin)->postJson("/api/v1/imports/{$importJob->id}/confirm", [
        'column_mapping' => ['name' => 'Full Name', 'email' => 'Email Address', 'phone' => 'Phone Number'],
    ]);

    $result->assertOk()->assertJsonPath('data.status', 'mapping_confirmed');
    Queue::assertPushed(ImportRespondentsJob::class);
});

test('confirming an already confirmed import is rejected', function () {
    [$organization, $admin] = orgAdminForImports();
    $importJob = ImportJob::factory()->create(['organization_id' => $organization->id, 'status' => 'completed']);

    $this->actingAs($admin)->postJson("/api/v1/imports/{$importJob->id}/confirm", [
        'column_mapping' => ['name' => 'Full Name'],
    ])->assertUnprocessable();
});

test('the import job creates new respondents and skips duplicates and blank rows', function () {
    [$organization, $admin] = orgAdminForImports();
    Respondent::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Existing Person',
        'email' => 'existing@example.com',
    ]);

    $file = fakeRespondentSpreadsheet([
        ['Jane Doe', 'jane@example.com', '01700000000'],
        ['Existing Person', 'existing@example.com', ''],
        ['', '', ''],
        ['', 'noname@example.com', ''],
    ]);

    $uploadResult = $this->actingAs($admin)->postJson('/api/v1/imports', ['file' => $file]);
    $importJob = ImportJob::find($uploadResult->json('data.id'));

    $this->actingAs($admin)->postJson("/api/v1/imports/{$importJob->id}/confirm", [
        'column_mapping' => ['name' => 'Full Name', 'email' => 'Email Address', 'phone' => 'Phone Number'],
    ])->assertOk();

    $importJob->refresh();
    expect($importJob->status)->toBe('completed');
    expect($importJob->imported_count)->toBe(1);
    expect($importJob->duplicate_count)->toBe(1);
    expect($importJob->failed_count)->toBe(1);

    // name/email/phone are 'encrypted' casts (App\Models\Respondent), so
    // the stored value is ciphertext and can't be matched with a WHERE
    // clause or assertDatabaseHas — load every row for the org and filter
    // in PHP, which decrypts on access.
    $imported = Respondent::query()->where('organization_id', $organization->id)->get()
        ->first(fn ($respondent) => $respondent->email === 'jane@example.com');
    expect($imported)->not->toBeNull();
    expect($imported->name)->toBe('Jane Doe');
});

test('the import history endpoint lists prior imports', function () {
    [$organization, $admin] = orgAdminForImports();
    ImportJob::factory()->count(2)->create(['organization_id' => $organization->id]);

    $result = $this->actingAs($admin)->getJson('/api/v1/imports');

    $result->assertOk()->assertJsonCount(2, 'data');
});

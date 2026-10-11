<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ImportJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    /** @use HasFactory<ImportJobFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'requested_by',
        'original_filename',
        'file_path',
        'status',
        'detected_columns',
        'column_mapping',
    ];

    protected function casts(): array
    {
        return [
            'detected_columns' => 'array',
            'column_mapping' => 'array',
            'row_errors' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function markMappingConfirmed(array $columnMapping): void
    {
        $this->forceFill([
            'column_mapping' => $columnMapping,
            'status' => 'mapping_confirmed',
        ])->save();
    }

    public function markCompleted(int $total, int $imported, int $duplicates, int $failed, array $rowErrors): void
    {
        $this->forceFill([
            'status' => 'completed',
            'total_rows' => $total,
            'imported_count' => $imported,
            'duplicate_count' => $duplicates,
            'failed_count' => $failed,
            'row_errors' => $rowErrors,
            'completed_at' => now(),
        ])->save();
    }

    public function markFailed(string $reason): void
    {
        $this->forceFill([
            'status' => 'failed',
            'failure_reason' => $reason,
            'completed_at' => now(),
        ])->save();
    }
}

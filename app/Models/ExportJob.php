<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ExportJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportJob extends Model
{
    /** @use HasFactory<ExportJobFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'survey_id',
        'requested_by',
        'type',
        'format',
        'status',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function markCompleted(string $filePath, int $rowCount): void
    {
        $this->forceFill([
            'status' => 'completed',
            'file_path' => $filePath,
            'row_count' => $rowCount,
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

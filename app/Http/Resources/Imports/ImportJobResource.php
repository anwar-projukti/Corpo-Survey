<?php

namespace App\Http\Resources\Imports;

use App\Models\ImportJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ImportJob
 */
class ImportJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_filename' => $this->original_filename,
            'status' => $this->status,
            'detected_columns' => $this->detected_columns,
            'column_mapping' => $this->column_mapping,
            'total_rows' => $this->total_rows,
            'imported_count' => $this->imported_count,
            'duplicate_count' => $this->duplicate_count,
            'failed_count' => $this->failed_count,
            'row_errors' => $this->when($this->status === 'completed', $this->row_errors),
            'failure_reason' => $this->when($this->status === 'failed', $this->failure_reason),
            'requested_by' => $this->whenLoaded('requester', fn () => $this->requester?->name),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}

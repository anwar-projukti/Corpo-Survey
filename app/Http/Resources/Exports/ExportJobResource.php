<?php

namespace App\Http\Resources\Exports;

use App\Models\ExportJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin ExportJob
 */
class ExportJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'format' => $this->format,
            'status' => $this->status,
            'row_count' => $this->row_count,
            'failure_reason' => $this->when($this->status === 'failed', $this->failure_reason),
            'download_url' => $this->when(
                $this->status === 'completed',
                fn () => URL::temporarySignedRoute('api.exports.download', now()->addHours(24), ['exportJob' => $this->id]),
            ),
            'requested_by' => $this->whenLoaded('requester', fn () => $this->requester?->name),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}

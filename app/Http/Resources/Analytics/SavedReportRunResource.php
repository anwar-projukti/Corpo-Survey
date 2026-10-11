<?php

namespace App\Http\Resources\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A saved report's live-computed result (spec §9), wrapped for Scramble
 * response-schema inference (spec §11.2). Deliberately not cached (see
 * App\Support\SavedReportRunner's own docblock) — this Resource is purely
 * a schema wrapper, not a change to that behavior.
 *
 * @mixin array{response_count: int, questions: array}
 */
class SavedReportRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}

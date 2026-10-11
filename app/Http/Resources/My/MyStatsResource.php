<?php

namespace App\Http\Resources\My;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A field user's own running totals (spec §7). Wraps the plain array
 * App\Http\Controllers\Api\V1\My\MyStatsController already builds — kept
 * as a Resource (rather than a raw response()->json()) purely so Scramble
 * can infer and document this endpoint's response schema (spec §11.2).
 *
 * @mixin array{today: int, this_week: int, total: int, by_survey: array<int, array{survey_id: int, survey_title: string|null, count: int}>}
 */
class MyStatsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}

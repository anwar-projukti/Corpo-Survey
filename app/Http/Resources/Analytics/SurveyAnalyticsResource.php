<?php

namespace App\Http\Resources\Analytics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Survey overview metrics, response trend, per-question drop-off, and
 * per-question answer distributions (spec §9), wrapped for Scramble
 * response-schema inference (spec §11.2).
 *
 * @mixin array{version_id: int, overview: array, trend: array, drop_off: array, questions: array}
 */
class SurveyAnalyticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}

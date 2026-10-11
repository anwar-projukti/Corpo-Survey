<?php

namespace App\Http\Controllers\Api\V1\My;

use App\Http\Controllers\Controller;
use App\Http\Resources\My\MyStatsResource;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;

/**
 * A field user's own running totals (spec §7 — "You've collected 23
 * responses today"). Scoped to collected_by_user_id = the authenticated
 * user only, at the query level — never another user's data, enforced by
 * the query itself rather than a client-supplied user_id parameter.
 */
class MyStatsController extends Controller
{
    public function index(Request $request): MyStatsResource
    {
        $collectorId = $request->user()->id;

        $base = SurveyResponse::query()->where('collected_by_user_id', $collectorId);

        return new MyStatsResource([
            'today' => (clone $base)->whereDate('submitted_at', now()->toDateString())->count(),
            'this_week' => (clone $base)->where('submitted_at', '>=', now()->startOfWeek())->count(),
            'total' => (clone $base)->count(),
            'by_survey' => (clone $base)
                ->selectRaw('survey_id, count(*) as count')
                ->groupBy('survey_id')
                ->with('survey:id,title')
                ->get()
                ->map(fn ($row) => [
                    'survey_id' => $row->survey_id,
                    'survey_title' => $row->survey?->title,
                    'count' => $row->count,
                ]),
        ]);
    }
}

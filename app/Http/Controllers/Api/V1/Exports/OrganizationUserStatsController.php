<?php

namespace App\Http\Controllers\Api\V1\Exports;

use App\Http\Controllers\Controller;
use App\Http\Resources\Exports\UserSurveyStatResource;
use App\Models\Organization;
use App\Models\UserSurveyStat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * All field users, ranked by volume (spec §10.3) — the org-wide
 * productivity report, exportable to Excel on its own or as a sheet in the
 * org-wide export (App\Exports\OrganizationSummarySheet covers the
 * per-survey rollup; this endpoint covers the per-user rollup the admin UI
 * table itself reads from).
 */
class OrganizationUserStatsController extends Controller
{
    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', UserSurveyStat::class);
        abort_unless($organization->id === $request->user()->organization_id, 403);

        $perPage = min((int) $request->integer('per_page', 20), 100);

        $stats = UserSurveyStat::query()
            ->with(['user:id,name', 'survey:id,title'])
            ->orderByDesc('response_count')
            ->paginate($perPage);

        return UserSurveyStatResource::collection($stats);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Exports;

use App\Http\Controllers\Controller;
use App\Http\Resources\Exports\UserSurveyStatResource;
use App\Models\User;
use App\Models\UserSurveyStat;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * One user's productivity totals, per survey (spec §10.3).
 */
class UserStatsController extends Controller
{
    public function index(User $user): AnonymousResourceCollection
    {
        $this->authorize('viewAny', UserSurveyStat::class);
        abort_unless($user->organization_id === auth()->user()->organization_id, 403);

        $stats = UserSurveyStat::query()
            ->where('user_id', $user->id)
            ->with('survey:id,title')
            ->orderByDesc('response_count')
            ->get();

        return UserSurveyStatResource::collection($stats);
    }
}

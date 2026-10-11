<?php

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Concerns\ResolvesSurveyVersion;
use App\Http\Controllers\Controller;
use App\Http\Resources\Analytics\QuestionDistributionResource;
use App\Http\Resources\Analytics\SurveyAnalyticsResource;
use App\Models\Survey;
use App\Support\SurveyAnalytics;
use Illuminate\Http\Request;

/**
 * Survey overview metrics, response trend, per-question drop-off, and
 * per-question answer distributions (spec §9) — everything a survey's
 * analytics dashboard needs in one payload, scoped to one version at a
 * time like the response table itself (App\Http\Controllers\Concerns\ResolvesSurveyVersion).
 */
class SurveyAnalyticsController extends Controller
{
    use ResolvesSurveyVersion;

    public function show(Request $request, Survey $survey, SurveyAnalytics $analytics): SurveyAnalyticsResource
    {
        $this->authorize('viewReports', $survey);

        $version = $this->resolveVersion($request, $survey);
        $version->load('sections.questions.section');

        $granularity = in_array($request->string('granularity')->value(), ['day', 'week', 'month'], true)
            ? $request->string('granularity')->value()
            : 'day';

        $questions = $version->sections->flatMap->questions;

        return new SurveyAnalyticsResource([
            'version_id' => $version->id,
            'overview' => $analytics->overview($version),
            'trend' => $analytics->trend($version, $granularity),
            'drop_off' => $analytics->dropOff($version),
            'questions' => $questions
                ->map(fn ($question) => (new QuestionDistributionResource($question, $analytics))->toArray($request))
                ->values(),
        ]);
    }
}

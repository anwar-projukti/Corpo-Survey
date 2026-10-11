<?php

namespace App\Http\Controllers\Api\V1\Responses;

use App\Http\Controllers\Concerns\ResolvesSurveyVersion;
use App\Http\Controllers\Controller;
use App\Http\Resources\Responses\SurveyResponseResource;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Services\AuditLogger;
use App\Support\ResponseFilterEngine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The advanced response table (spec §8.1): search, filter, sort,
 * pagination. Scoped to one survey version at a time (spec §6.2's
 * versioning means each version can have a different question set, so
 * "filter by Age > 25" only makes sense against one specific version's
 * own question ids) — defaults to the survey's current published version.
 */
class SurveyResponseController extends Controller
{
    use ResolvesSurveyVersion;

    public function __construct(private readonly AuditLogger $auditLogger) {}

    private const SORTABLE_COLUMNS = ['submitted_at', 'started_at', 'created_at', 'status', 'review_status'];

    public function index(Request $request, Survey $survey): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SurveyResponse::class);
        $this->authorize('view', $survey);

        $version = $this->resolveVersion($request, $survey);

        // answers is loaded here (not just on show()) — the table's own
        // answer-value columns read straight off each row's own answers,
        // so leaving it out means every answer cell silently renders
        // blank instead of a real value.
        $query = SurveyResponse::query()
            ->where('survey_version_id', $version->id)
            ->with(['collector', 'answers']);

        $this->applyFilters($request, $query, $version);
        $this->applySort($request, $query);

        $perPage = min((int) $request->integer('per_page', 20), 100);

        return SurveyResponseResource::collection($query->paginate($perPage));
    }

    public function show(Survey $survey, SurveyResponse $response): SurveyResponseResource
    {
        $this->authorize('view', $response);
        abort_unless($response->survey_id === $survey->id, 404);

        return new SurveyResponseResource(
            $response->load(['answers', 'notes.user', 'collector', 'respondent'])
        );
    }

    public function destroy(Request $request, Survey $survey, SurveyResponse $response): JsonResponse
    {
        $this->authorize('delete', $response);
        abort_unless($response->survey_id === $survey->id, 404);

        // Logged before delete() (not after) for the same reason the audit
        // entry itself matters here (spec §5.10) — the subject must still
        // resolve to a real row for the morph relation to record something
        // meaningful, and a soft delete happening a moment later doesn't
        // change what's true to log: this response existed and was deleted
        // by this actor at this time.
        $this->auditLogger->log('response.deleted', subject: $response, properties: [
            'survey_id' => $survey->id,
        ], actor: $request->user());

        $response->delete();

        return $this->successResponse('Response deleted.');
    }

    /**
     * @param  Builder<SurveyResponse>  $query
     */
    private function applyFilters(Request $request, Builder $query, SurveyVersion $version): void
    {
        $filters = $request->input('filters', []);

        if (! is_array($filters) || $filters === []) {
            return;
        }

        $validQuestionIds = $version->sections()
            ->with('questions:id,survey_section_id')
            ->get()
            ->flatMap->questions
            ->pluck('id')
            ->all();

        ResponseFilterEngine::validate($filters, $validQuestionIds);
        ResponseFilterEngine::apply($query, $filters);
    }

    /**
     * @param  Builder<SurveyResponse>  $query
     */
    private function applySort(Request $request, Builder $query): void
    {
        $sort = $request->string('sort', '-submitted_at')->value();
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            $column = 'submitted_at';
            $direction = 'desc';
        }

        $query->orderBy($column, $direction);
    }
}

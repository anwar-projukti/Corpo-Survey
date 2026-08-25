<?php

namespace App\Http\Middleware;

use App\Models\Survey;
use App\Services\PublicSurveyAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the public survey JSON API (spec §5.7/§5.8) — these endpoints are
 * called via fetch() from the respondent's already-loaded page, so they
 * carry no signature of their own to verify. Instead they require that
 * GrantsPublicSurveyAccess already recorded this exact survey id in the
 * session (PublicSurveyAccess::grantAccess), which only happens after a
 * request that DID carry a valid signature for it (the page route). A
 * session with no such grant for this survey id is rejected outright.
 */
class RequirePublicSurveyAccess
{
    public function __construct(private readonly PublicSurveyAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Survey $survey */
        $survey = $request->route('survey');

        if (! $this->access->hasAccess($request, $survey)) {
            abort(403);
        }

        return $next($request);
    }
}

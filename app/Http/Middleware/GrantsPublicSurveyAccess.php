<?php

namespace App\Http\Middleware;

use App\Models\Survey;
use App\Services\PublicSurveyAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs after the "signed" middleware on the public survey page route
 * (spec §5.8) — reaching this point already proves the request carried a
 * valid signature for this exact survey. That's recorded in the session
 * (PublicSurveyAccess::grantAccess) so the page's own fetch() calls to the
 * public JSON API (which have no signature of their own to carry) can be
 * gated by RequirePublicSurveyAccess instead of re-deriving a signature
 * client-side.
 *
 * Session-scoped rather than a stored token: it only grants access for as
 * long as this respondent's own browser session lasts, never becomes a
 * second shareable credential, and needs no extra table/cleanup job.
 */
class GrantsPublicSurveyAccess
{
    public function __construct(private readonly PublicSurveyAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Survey $survey */
        $survey = $request->route('survey');

        $this->access->grantAccess($request, $survey);

        return $next($request);
    }
}

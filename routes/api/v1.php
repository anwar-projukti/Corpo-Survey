<?php

use App\Http\Controllers\Api\V1\Account\AccountDataController;
use App\Http\Controllers\Api\V1\Admin\LandingContentController;
use App\Http\Controllers\Api\V1\Admin\OrganizationController;
use App\Http\Controllers\Api\V1\Admin\PricingPlanController;
use App\Http\Controllers\Api\V1\Analytics\SavedReportController;
use App\Http\Controllers\Api\V1\Analytics\SurveyAnalyticsController;
use App\Http\Controllers\Api\V1\Exports\ExportJobController;
use App\Http\Controllers\Api\V1\Exports\OrganizationExportController;
use App\Http\Controllers\Api\V1\Exports\OrganizationUserStatsController;
use App\Http\Controllers\Api\V1\Exports\SurveyExportController;
use App\Http\Controllers\Api\V1\Exports\UserStatsController;
use App\Http\Controllers\Api\V1\Imports\RespondentImportController;
use App\Http\Controllers\Api\V1\My\MyResponseController;
use App\Http\Controllers\Api\V1\My\MyStatsController;
use App\Http\Controllers\Api\V1\My\MySurveyController;
use App\Http\Controllers\Api\V1\PublicSurveys\PublicSurveyController;
use App\Http\Controllers\Api\V1\PublicSurveys\PublicSurveyOtpController;
use App\Http\Controllers\Api\V1\PublicSurveys\PublicSurveyResponseController;
use App\Http\Controllers\Api\V1\Responses\ResponseAnswerController;
use App\Http\Controllers\Api\V1\Responses\ResponseNoteController;
use App\Http\Controllers\Api\V1\Responses\ResponseReviewController;
use App\Http\Controllers\Api\V1\Responses\SurveyResponseController;
use App\Http\Controllers\Api\V1\Surveys\QuestionOptionController;
use App\Http\Controllers\Api\V1\Surveys\SurveyAssignmentController;
use App\Http\Controllers\Api\V1\Surveys\SurveyController;
use App\Http\Controllers\Api\V1\Surveys\SurveyQuestionController;
use App\Http\Controllers\Api\V1\Surveys\SurveyRuleController;
use App\Http\Controllers\Api\V1\Surveys\SurveySectionController;
use App\Http\Controllers\Api\V1\Surveys\SurveyVersionController;
use App\Http\Controllers\Api\V1\Users\TeammateController;
use App\Http\Middleware\RequirePublicSurveyAccess;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

// Public survey access (spec §5.7/§5.8) — unauthenticated by design, and
// deliberately on its own rate-limit tier separate from the admin API's
// (a burst of anonymous respondents must never be able to exhaust the
// same bucket an org's own admin traffic relies on). The respondent-facing
// page (routes/web.php's "public.surveys.show") is reached only via a
// signed link and stamps the session via GrantsPublicSurveyAccess; these
// JSON endpoints are called by that page's own fetch() calls, which carry
// no signature of their own, so they check that session grant instead
// (RequirePublicSurveyAccess) rather than re-verifying a signature.
//
// EncryptCookies + StartSession + ValidateCsrfToken are added explicitly
// rather than relying on Sanctum's statefulApi()/EnsureFrontendRequestsAreStateful,
// which only runs its own inner session/CSRF pipeline when it heuristically
// recognizes the request as "from the frontend" (matching Referer/Origin
// against sanctum.stateful) — too fragile a foundation for guarantees this
// route group needs on every request. CSRF is kept rather than dropped so
// this group stays consistent with every other stateful, cookie-backed
// write path in the app; resources/js/bootstrap.js already attaches
// X-CSRF-TOKEN to every axios request, so the public form's own fetch
// calls satisfy it for free.
Route::middleware(['throttle:public-survey', EncryptCookies::class, StartSession::class, ValidateCsrfToken::class, RequirePublicSurveyAccess::class])
    ->prefix('public/surveys/{survey}')
    ->group(function () {
        Route::get('/', [PublicSurveyController::class, 'show'])->name('public.surveys.api.show');
        Route::post('/otp', [PublicSurveyOtpController::class, 'send'])->middleware('throttle:public-survey-otp')->name('public.surveys.otp.send');
        Route::post('/otp/verify', [PublicSurveyOtpController::class, 'verify'])->middleware('throttle:public-survey-otp')->name('public.surveys.otp.verify');
        Route::post('/responses', [PublicSurveyResponseController::class, 'store'])->name('public.surveys.responses.store');
    });

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Named with an api. prefix so its auto-generated route names
    // (surveys.index, surveys.show, ...) don't collide with the Blade
    // page routes of the same name in routes/web.php — apiResource()'s
    // default names aren't namespaced by the api/v1 prefix the URLs
    // themselves get, and whichever route file loads last silently wins
    // route('surveys.index') everywhere it's called.
    Route::apiResource('surveys', SurveyController::class)->only(['index', 'store', 'show', 'update', 'destroy'])->names('api.surveys');
    // {survey} above resolves only non-trashed rows by default (see the
    // "Deleted organizations" comment on api.organizations.restore) — this
    // one exists specifically to act on an already soft-deleted survey.
    Route::post('surveys/{survey}/restore', [SurveyController::class, 'restore'])->name('api.surveys.restore');
    Route::post('surveys/{survey}/publish', [SurveyController::class, 'publish'])->name('surveys.publish');
    Route::post('surveys/{survey}/close', [SurveyController::class, 'close'])->name('surveys.close');
    Route::post('surveys/{survey}/draft', [SurveyController::class, 'createDraft'])->name('surveys.create-draft');
    Route::get('surveys/{survey}/public-link', [SurveyController::class, 'publicLink'])->name('surveys.public-link');

    // Door-to-door field collection (spec §7) — which field users may
    // collect responses for a given survey.
    Route::get('surveys/{survey}/assignments', [SurveyAssignmentController::class, 'index'])->name('survey-assignments.index');
    Route::get('surveys/{survey}/assignable-users', [SurveyAssignmentController::class, 'assignable'])->name('survey-assignments.assignable');
    Route::post('surveys/{survey}/assignments', [SurveyAssignmentController::class, 'store'])->name('survey-assignments.store');
    Route::delete('surveys/{survey}/assignments/{assignment}', [SurveyAssignmentController::class, 'destroy'])->name('survey-assignments.destroy');

    Route::get('survey-versions/{version}', [SurveyVersionController::class, 'show'])->name('survey-versions.show');

    Route::post('survey-versions/{version}/sections', [SurveySectionController::class, 'store'])->name('survey-sections.store');
    Route::post('survey-versions/{version}/sections/reorder', [SurveySectionController::class, 'reorder'])->name('survey-sections.reorder');
    Route::patch('survey-sections/{section}', [SurveySectionController::class, 'update'])->name('survey-sections.update');
    Route::delete('survey-sections/{section}', [SurveySectionController::class, 'destroy'])->name('survey-sections.destroy');

    Route::post('survey-sections/{section}/questions', [SurveyQuestionController::class, 'store'])->name('survey-questions.store');
    Route::post('survey-sections/{section}/questions/reorder', [SurveyQuestionController::class, 'reorder'])->name('survey-questions.reorder');
    Route::patch('survey-questions/{question}', [SurveyQuestionController::class, 'update'])->name('survey-questions.update');
    Route::delete('survey-questions/{question}', [SurveyQuestionController::class, 'destroy'])->name('survey-questions.destroy');

    Route::post('survey-questions/{question}/options', [QuestionOptionController::class, 'store'])->name('question-options.store');
    Route::post('survey-questions/{question}/options/reorder', [QuestionOptionController::class, 'reorder'])->name('question-options.reorder');
    Route::patch('question-options/{option}', [QuestionOptionController::class, 'update'])->name('question-options.update');
    Route::delete('question-options/{option}', [QuestionOptionController::class, 'destroy'])->name('question-options.destroy');

    Route::post('survey-versions/{version}/rules', [SurveyRuleController::class, 'store'])->name('survey-rules.store');
    Route::patch('survey-rules/{rule}', [SurveyRuleController::class, 'update'])->name('survey-rules.update');
    Route::delete('survey-rules/{rule}', [SurveyRuleController::class, 'destroy'])->name('survey-rules.destroy');

    // Response management (spec §8) — the advanced response table, detail
    // view, approve/reject/archive review, notes, and audited answer edits.
    // Named with an api. prefix — see the apiResource('surveys', ...) note
    // above; routes/web.php has its own responses.index/responses.show
    // for the Blade pages themselves.
    Route::get('surveys/{survey}/responses', [SurveyResponseController::class, 'index'])->name('api.responses.index');
    Route::get('surveys/{survey}/responses/{response}', [SurveyResponseController::class, 'show'])->name('api.responses.show');
    Route::delete('surveys/{survey}/responses/{response}', [SurveyResponseController::class, 'destroy'])->name('api.responses.destroy');

    Route::post('responses/{response}/review', [ResponseReviewController::class, 'store'])->name('responses.review');
    Route::post('responses/{response}/notes', [ResponseNoteController::class, 'store'])->name('responses.notes.store');
    Route::patch('response-answers/{answer}', [ResponseAnswerController::class, 'update'])->name('response-answers.update');

    // Analytics (spec §9) — survey overview metrics, response trend,
    // per-question drop-off, and per-question answer distributions.
    // Named with an api. prefix — routes/web.php has its own
    // analytics.show for the Blade page itself.
    Route::get('surveys/{survey}/analytics', [SurveyAnalyticsController::class, 'show'])->name('api.analytics.show');

    // Custom report builder (spec §9) — saved report configurations and
    // running one against live data.
    Route::get('surveys/{survey}/saved-reports', [SavedReportController::class, 'index'])->name('saved-reports.index');
    Route::post('surveys/{survey}/saved-reports', [SavedReportController::class, 'store'])->name('saved-reports.store');
    Route::patch('saved-reports/{savedReport}', [SavedReportController::class, 'update'])->name('saved-reports.update');
    Route::delete('saved-reports/{savedReport}', [SavedReportController::class, 'destroy'])->name('saved-reports.destroy');
    Route::get('saved-reports/{savedReport}/run', [SavedReportController::class, 'run'])->name('saved-reports.run');

    // Excel import/export & user productivity reporting (spec §10). Export
    // creation is always queued (spec §10.4) — the response is the created
    // App\Models\ExportJob to poll, not the file itself; the actual
    // download only ever happens via the signed link in routes/web.php
    // (api.exports.download), never through this authenticated group,
    // since a signed link must also work from an email client with no
    // Sanctum session.
    Route::get('exports', [ExportJobController::class, 'index'])->name('exports.index');

    Route::middleware('throttle:exports')->group(function () {
        Route::post('surveys/{survey}/export', [SurveyExportController::class, 'store'])->name('surveys.export');
        Route::post('organizations/{organization}/export', [OrganizationExportController::class, 'store'])->name('organizations.export');

        // Excel import (spec §10.4): upload -> preview -> column mapping ->
        // validation -> duplicate detection -> import summary, one endpoint
        // per step so the admin UI can render a preview/mapping screen
        // between upload and the actual (queued) import run.
        Route::post('imports', [RespondentImportController::class, 'store'])->name('imports.store');
        Route::post('imports/{importJob}/confirm', [RespondentImportController::class, 'confirm'])->name('imports.confirm');
    });

    Route::get('users/{user}/stats', [UserStatsController::class, 'index'])->name('users.stats');
    Route::get('organizations/{organization}/user-stats', [OrganizationUserStatsController::class, 'index'])->name('organizations.user-stats');

    Route::get('imports', [RespondentImportController::class, 'index'])->name('imports.index');
    Route::get('imports/{importJob}', [RespondentImportController::class, 'show'])->name('imports.show');

    // Org Admin managing their own organization's teammates (spec §13
    // "Users & Permissions") — named "teammates" rather than "users" to
    // avoid colliding with the users/{user}/stats route registered above,
    // and because this list is always scoped to the acting admin's own
    // organization, never "every user in the system" the way that name
    // might imply.
    Route::apiResource('teammates', TeammateController::class)->only(['index', 'store', 'update', 'destroy'])->names('teammates');

    // Super Admin managing every organization in the system (spec §13
    // "System Admin" surface) — distinct from teammates above, which is
    // always scoped to the acting admin's own organization. Every action
    // is gated by OrganizationPolicy, which only Super Admin ever passes.
    // Named with an api. prefix — see the apiResource('surveys', ...) note
    // above; routes/web.php has its own organizations.index for the Blade
    // page itself, which would otherwise collide with this index route.
    Route::apiResource('organizations', OrganizationController::class)->only(['index', 'store', 'update', 'destroy'])->names('api.organizations');

    // Restoring/permanently erasing a soft-deleted organization (the
    // "Deleted organizations" list, ?trashed=1 on the index above). Plain
    // int route params rather than {organization} route-model binding —
    // binding resolves only non-trashed rows by default, and both actions
    // exist specifically to act on an already-trashed one.
    Route::post('organizations/{organization}/restore', [OrganizationController::class, 'restore'])->name('api.organizations.restore');
    Route::delete('organizations/{organization}/force', [OrganizationController::class, 'forceDestroy'])->name('api.organizations.force-destroy');

    // Super Admin editing the public landing page's content (hero,
    // features, pricing, about, contact) — a single row, so show/update
    // rather than a full resource. Gated by LandingContentPolicy, which
    // only Super Admin ever passes.
    Route::get('landing-content', [LandingContentController::class, 'show'])->name('api.landing-content.show');
    Route::put('landing-content', [LandingContentController::class, 'update'])->name('api.landing-content.update');
    Route::post('landing-content/logo', [LandingContentController::class, 'uploadLogo'])->name('api.landing-content.logo.store');
    Route::delete('landing-content/logo', [LandingContentController::class, 'destroyLogo'])->name('api.landing-content.logo.destroy');

    // Super Admin managing the public landing page's pricing tiers — a
    // genuine list (add/remove/reorder), unlike landing-content's single
    // row above. Named with an api. prefix — routes/web.php has its own
    // pricing-plans.index for the Blade page itself.
    Route::apiResource('pricing-plans', PricingPlanController::class)->only(['index', 'store', 'update', 'destroy'])->names('api.pricing-plans');
    Route::post('pricing-plans/reorder', [PricingPlanController::class, 'reorder'])->name('api.pricing-plans.reorder');

    // Door-to-door field collection (spec §7) — a Field User's own scoped
    // view: only surveys assigned to them, submission tagged with their
    // own identity server-side, and their own running totals. Deliberately
    // separate from the admin /surveys/... endpoints above rather than an
    // "if field user, filter" branch on the same routes — the access
    // pattern (assigned-only, self-scoped) is different enough to warrant
    // its own controllers.
    // Named with an api. prefix — routes/web.php has its own
    // my.surveys.index/my.surveys.show for the Blade pages themselves.
    Route::prefix('my')->name('api.my.')->group(function () {
        Route::get('surveys', [MySurveyController::class, 'index'])->name('surveys.index');
        Route::get('surveys/{survey}', [MySurveyController::class, 'show'])->name('surveys.show');
        Route::post('surveys/{survey}/responses', [MyResponseController::class, 'store'])->name('surveys.responses.store');
        Route::get('responses', [MyResponseController::class, 'index'])->name('responses.index');
        Route::get('responses/{response}', [MyResponseController::class, 'show'])->name('responses.show');
        Route::get('stats', [MyStatsController::class, 'index'])->name('stats');
    });

    // Self-service GDPR-style data export / right-to-be-forgotten (spec
    // §5.12) — always the authenticated user's own account, never another
    // user's, so no route parameter or policy is needed.
    Route::get('me/data-export', [AccountDataController::class, 'export'])->name('me.data-export');
    Route::post('me/anonymize', [AccountDataController::class, 'anonymize'])->name('me.anonymize');
});

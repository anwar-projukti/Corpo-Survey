<?php

use App\Http\Controllers\Api\V1\Analytics\SurveyAnalyticsController;
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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('surveys', SurveyController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('surveys/{survey}/publish', [SurveyController::class, 'publish'])->name('surveys.publish');
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
    Route::get('surveys/{survey}/responses', [SurveyResponseController::class, 'index'])->name('responses.index');
    Route::get('surveys/{survey}/responses/{response}', [SurveyResponseController::class, 'show'])->name('responses.show');
    Route::delete('surveys/{survey}/responses/{response}', [SurveyResponseController::class, 'destroy'])->name('responses.destroy');

    Route::post('responses/{response}/review', [ResponseReviewController::class, 'store'])->name('responses.review');
    Route::post('responses/{response}/notes', [ResponseNoteController::class, 'store'])->name('responses.notes.store');
    Route::patch('response-answers/{answer}', [ResponseAnswerController::class, 'update'])->name('response-answers.update');

    // Analytics (spec §9) — survey overview metrics, response trend,
    // per-question drop-off, and per-question answer distributions.
    Route::get('surveys/{survey}/analytics', [SurveyAnalyticsController::class, 'show'])->name('analytics.show');

    // Door-to-door field collection (spec §7) — a Field User's own scoped
    // view: only surveys assigned to them, submission tagged with their
    // own identity server-side, and their own running totals. Deliberately
    // separate from the admin /surveys/... endpoints above rather than an
    // "if field user, filter" branch on the same routes — the access
    // pattern (assigned-only, self-scoped) is different enough to warrant
    // its own controllers.
    Route::prefix('my')->name('my.')->group(function () {
        Route::get('surveys', [MySurveyController::class, 'index'])->name('surveys.index');
        Route::get('surveys/{survey}', [MySurveyController::class, 'show'])->name('surveys.show');
        Route::post('surveys/{survey}/responses', [MyResponseController::class, 'store'])->name('surveys.responses.store');
        Route::get('stats', [MyStatsController::class, 'index'])->name('stats');
    });
});

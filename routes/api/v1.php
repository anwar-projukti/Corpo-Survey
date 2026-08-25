<?php

use App\Http\Controllers\Api\V1\Surveys\QuestionOptionController;
use App\Http\Controllers\Api\V1\Surveys\SurveyController;
use App\Http\Controllers\Api\V1\Surveys\SurveyQuestionController;
use App\Http\Controllers\Api\V1\Surveys\SurveyRuleController;
use App\Http\Controllers\Api\V1\Surveys\SurveySectionController;
use App\Http\Controllers\Api\V1\Surveys\SurveyVersionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('surveys', SurveyController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('surveys/{survey}/publish', [SurveyController::class, 'publish'])->name('surveys.publish');
    Route::post('surveys/{survey}/draft', [SurveyController::class, 'createDraft'])->name('surveys.create-draft');

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
});

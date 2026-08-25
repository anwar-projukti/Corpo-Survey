<?php

namespace App\Http\Controllers\Api\V1\Surveys;

use App\Actions\Surveys\ReorderItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Surveys\ReorderRequest;
use App\Http\Requests\Surveys\StoreQuestionRequest;
use App\Http\Requests\Surveys\UpdateQuestionRequest;
use App\Http\Resources\Surveys\SurveyQuestionResource;
use App\Models\SurveyQuestion;
use App\Models\SurveySection;
use Illuminate\Http\JsonResponse;

class SurveyQuestionController extends Controller
{
    public function store(StoreQuestionRequest $request, SurveySection $section): SurveyQuestionResource
    {
        $maxOrder = $section->questions()->max('order');

        $question = $section->questions()->create([
            ...$request->validated(),
            'order' => $maxOrder === null ? 0 : $maxOrder + 1,
        ]);

        return new SurveyQuestionResource($question->load('options'));
    }

    public function update(UpdateQuestionRequest $request, SurveyQuestion $question): SurveyQuestionResource
    {
        $question->update($request->validated());

        return new SurveyQuestionResource($question->fresh()->load('options'));
    }

    public function destroy(SurveyQuestion $question): JsonResponse
    {
        $this->authorize('edit', $question->section->version);

        $question->delete();

        return response()->json(['success' => true, 'message' => 'Question deleted.']);
    }

    public function reorder(ReorderRequest $request, SurveySection $section): JsonResponse
    {
        $this->authorize('edit', $section->version);

        app(ReorderItems::class)->handle($section->questions(), $request->validated('order'));

        return response()->json(['success' => true]);
    }
}

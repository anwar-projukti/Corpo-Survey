<?php

namespace App\Http\Controllers\Api\V1\Surveys;

use App\Actions\Surveys\ReorderItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Surveys\ReorderRequest;
use App\Http\Requests\Surveys\StoreOptionRequest;
use App\Http\Requests\Surveys\UpdateOptionRequest;
use App\Http\Resources\Surveys\QuestionOptionResource;
use App\Models\QuestionOption;
use App\Models\SurveyQuestion;
use Illuminate\Http\JsonResponse;

class QuestionOptionController extends Controller
{
    public function store(StoreOptionRequest $request, SurveyQuestion $question): QuestionOptionResource
    {
        $maxOrder = $question->options()->max('order');

        $option = $question->options()->create([
            ...$request->validated(),
            'order' => $maxOrder === null ? 0 : $maxOrder + 1,
        ]);

        return new QuestionOptionResource($option);
    }

    public function update(UpdateOptionRequest $request, QuestionOption $option): QuestionOptionResource
    {
        $option->update($request->validated());

        return new QuestionOptionResource($option->fresh());
    }

    public function destroy(QuestionOption $option): JsonResponse
    {
        $this->authorize('edit', $option->question->section->version);

        $option->delete();

        return response()->json(['success' => true, 'message' => 'Option deleted.']);
    }

    public function reorder(ReorderRequest $request, SurveyQuestion $question): JsonResponse
    {
        $this->authorize('edit', $question->section->version);

        app(ReorderItems::class)->handle($question->options(), $request->validated('order'));

        return response()->json(['success' => true]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Responses;

use App\Actions\Responses\EditResponseAnswer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Responses\UpdateResponseAnswerRequest;
use App\Http\Resources\Responses\ResponseAnswerResource;
use App\Models\ResponseAnswer;

class ResponseAnswerController extends Controller
{
    public function update(UpdateResponseAnswerRequest $request, ResponseAnswer $answer, EditResponseAnswer $editResponseAnswer): ResponseAnswerResource
    {
        $updated = $editResponseAnswer->handle($answer, $request->input('value'), $request->user());

        return new ResponseAnswerResource($updated);
    }
}

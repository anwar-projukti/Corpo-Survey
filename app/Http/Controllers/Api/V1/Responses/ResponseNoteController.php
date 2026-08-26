<?php

namespace App\Http\Controllers\Api\V1\Responses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Responses\StoreResponseNoteRequest;
use App\Http\Resources\Responses\ResponseNoteResource;
use App\Models\SurveyResponse;

class ResponseNoteController extends Controller
{
    public function store(StoreResponseNoteRequest $request, SurveyResponse $response): ResponseNoteResource
    {
        $note = $response->notes()->make(['body' => $request->validated('body')]);
        $note->forceFill(['user_id' => $request->user()->id])->save();

        return new ResponseNoteResource($note->load('user'));
    }
}

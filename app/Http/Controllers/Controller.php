<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * The spec §5.6/§11.2 API success envelope, for the handful of
     * endpoints with no natural Resource to return (e.g. a plain
     * confirmation after a delete, or a message-only public-survey
     * response) — every list/show/store/update endpoint should still
     * return a proper API Resource instead of this.
     */
    protected function successResponse(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, ...$data], $status);
    }

    /**
     * The spec §5.6/§11.2 API error envelope. $errors is omitted from the
     * payload entirely when empty, rather than sent as {} — "no errors
     * object" is itself a signal callers rely on (only a 422 ever carries
     * one).
     */
    protected function errorResponse(string $message, int $status = 422, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            ...($errors !== [] ? ['errors' => $errors] : []),
        ], $status);
    }
}

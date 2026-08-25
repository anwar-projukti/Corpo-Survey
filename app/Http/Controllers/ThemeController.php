<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateThemeRequest;
use Illuminate\Http\JsonResponse;

/**
 * Persists a logged-in user's theme choice server-side (users.theme_preference)
 * so it follows them across devices. The switch itself happens instantly and
 * client-side via Alpine.js + localStorage — this call updates the durable
 * record in the background and never blocks the UI.
 */
class ThemeController extends Controller
{
    public function __invoke(UpdateThemeRequest $request): JsonResponse
    {
        $request->user()->forceFill([
            'theme_preference' => $request->validated('theme'),
        ])->save();

        return response()->json(['saved' => true]);
    }
}

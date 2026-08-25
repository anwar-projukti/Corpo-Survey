<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Http\Requests\UpdateLocaleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    public function __invoke(UpdateLocaleRequest $request): RedirectResponse
    {
        $locale = $request->validated('locale');

        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            $user->forceFill(['locale' => $locale])->save();
        } else {
            $request->session()->put(SetLocale::SESSION_KEY, $locale);
        }

        return back();
    }
}

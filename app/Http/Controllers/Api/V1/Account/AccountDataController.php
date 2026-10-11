<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\AnonymizeAccount;
use App\Actions\Account\ExportAccountData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AnonymizeAccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Self-service GDPR-style data export and right-to-be-forgotten (spec
 * §5.12/§12) — scoped to the authenticated user's own account only, never
 * another user's, so there is deliberately no route parameter here at all.
 */
class AccountDataController extends Controller
{
    public function export(Request $request, ExportAccountData $export): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $export->handle($request->user())]);
    }

    public function anonymize(AnonymizeAccountRequest $request, AnonymizeAccount $anonymize): JsonResponse
    {
        $user = $request->user();

        $anonymize->handle($user);

        Auth::guard('web')->logout();

        // A session only exists on this request when Sanctum's stateful
        // middleware actually started one for this same-origin call (see
        // bootstrap/app.php's statefulApi()) — guarded rather than assumed,
        // since a bearer-token API caller has no session to invalidate at
        // all, and AnonymizeAccount::handle() has already done the part
        // that actually matters (revoking every access token).
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->successResponse('Your account has been anonymized and you have been logged out.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The dashboard landing page — a first-run onboarding checklist for a
 * brand-new organization (spec §12), replacing what used to be a bare
 * "Welcome, {name}" heading with zero guidance after registration. Once
 * an org has real activity (a published survey, at least one response),
 * the checklist gives way to an ordinary at-a-glance summary; a new Org
 * Admin never has to guess what to do first. A Super Admin has no
 * organization of their own (organization_id is null), so they get a
 * system-wide summary across every organization instead (spec §13
 * "System Admin") rather than the org-scoped checklist/summary below.
 */
class HomePageController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Every checklist step here — create a survey, publish it, invite
        // a field user — is something a Field User has no permission to
        // do (see database/seeders/RolesAndPermissionsSeeder). Sending
        // them to the door-to-door collection portal they actually use
        // (spec §7) instead of an admin dashboard built for Org
        // Admin/Editor. A user who also holds a higher-privileged role
        // still gets the admin dashboard.
        if ($user->isFieldUserOnly()) {
            return redirect()->route('my.surveys.index');
        }

        if ($user->isSuperAdmin()) {
            return view('home', [
                'isSystemAdmin' => true,
                'organizationCount' => Organization::query()->count(),
                'surveyCount' => Survey::query()->withoutGlobalScopes()->count(),
                'responseCount' => SurveyResponse::query()->withoutGlobalScopes()->count(),
                'userCount' => User::query()->count(),
                'recentOrganizations' => Organization::query()
                    ->withCount(['surveys', 'users'])
                    ->latest()
                    ->take(5)
                    ->get(),
            ]);
        }

        $organizationId = $user->organization_id;

        $surveyCount = Survey::query()->where('organization_id', $organizationId)->count();
        $publishedCount = Survey::query()->where('organization_id', $organizationId)->whereNotNull('current_published_version_id')->count();
        $fieldUserCount = User::query()->where('organization_id', $organizationId)->whereHas('roles', fn ($q) => $q->where('name', 'Field User'))->count();
        $responseCount = SurveyResponse::query()->where('organization_id', $organizationId)->count();

        $recentSurveys = Survey::query()
            ->where('organization_id', $organizationId)
            ->withCount('responses')
            ->latest()
            ->take(5)
            ->get();

        return view('home', [
            'isSystemAdmin' => false,
            'checklist' => [
                'has_survey' => $surveyCount > 0,
                'has_published_survey' => $publishedCount > 0,
                'has_field_user' => $fieldUserCount > 0,
                'has_response' => $responseCount > 0,
            ],
            'isOnboarding' => $responseCount === 0,
            'surveyCount' => $surveyCount,
            'publishedCount' => $publishedCount,
            'fieldUserCount' => $fieldUserCount,
            'responseCount' => $responseCount,
            'recentSurveys' => $recentSurveys,
        ]);
    }
}

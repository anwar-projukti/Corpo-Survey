<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Http\Resources\Organizations\OrganizationResource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Super Admin managing every organization in the system — distinct from
 * TeammateController, which lets an Org Admin manage only their own
 * organization's users. Every action here is gated by OrganizationPolicy,
 * which only Super Admin ever passes.
 */
class OrganizationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Organization::class);

        // ?trashed=1 shows only soft-deleted organizations (the "Deleted
        // organizations" list a Super Admin restores from or force-deletes
        // permanently) rather than the ordinary active/suspended list —
        // deliberately two separate queries/views rather than one merged
        // list, so an accidental restore/force-delete click is far less
        // likely than if live and deleted organizations sat side by side.
        $organizations = Organization::query()
            ->when(
                $request->boolean('trashed'),
                fn ($query) => $query->onlyTrashed(),
            )
            ->withCount(['users', 'surveys', 'responses'])
            ->with(['users' => fn ($query) => $query->whereHas(
                'roles', fn ($q) => $q->where('name', 'Org Admin')
            )->orderBy('name')])
            ->orderBy('name')
            ->get();

        return OrganizationResource::collection($organizations);
    }

    public function store(StoreOrganizationRequest $request): OrganizationResource
    {
        $organization = Organization::create($request->validated());

        return new OrganizationResource($organization);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): OrganizationResource
    {
        $organization->update($request->validated());

        return new OrganizationResource($organization);
    }

    public function destroy(Organization $organization): Response
    {
        $this->authorize('delete', $organization);

        // A soft delete (Organization uses SoftDeletes), not a hard one:
        // every child table's organization_id FK is either cascadeOnDelete
        // (surveys, responses, respondents, ...) or nullOnDelete (users) —
        // both of which only fire on an actual row deletion, never a
        // soft-deleted row's still-present organization_id. A hard delete
        // here would either cascade-erase every survey/response this
        // organization has, or silently strand its users with
        // organization_id = null while keeping their org-scoped roles
        // (Org Admin, Field User, ...) — neither is a one-click action
        // System Admin should be able to trigger by accident. Soft
        // delete just removes it from this list; every survey/response/
        // user underneath stays intact and the org can be restored later
        // via restore(), or permanently erased via forceDestroy() once
        // they're certain.
        $organization->delete();

        return response()->noContent();
    }

    public function restore(Request $request, int $organization): OrganizationResource
    {
        $organization = Organization::onlyTrashed()->findOrFail($organization);

        $this->authorize('restore', $organization);

        $organization->restore();

        return new OrganizationResource($organization);
    }

    public function forceDestroy(int $organization): Response
    {
        $organization = Organization::onlyTrashed()->findOrFail($organization);

        $this->authorize('forceDelete', $organization);

        // Unlike destroy() above, this really does cascade: every survey,
        // response, and respondent this organization owns is erased with
        // it (cascadeOnDelete). Only reachable for an organization already
        // soft-deleted (onlyTrashed() above), so this is always the
        // deliberate second step after destroy(), never a way to skip the
        // soft-delete safety net.
        //
        // Its remaining users (Org Admin, teammates — anyone not already
        // removed individually) are force-deleted here too, explicitly,
        // rather than left to the users.organization_id FK's own
        // nullOnDelete: leaving them behind would strand permanently
        // org-less, role-less accounts that still occupy their email
        // address in the unique index, silently blocking that email from
        // ever registering a new organization again ("The email has
        // already been taken") with no UI anywhere showing why.
        DB::transaction(function () use ($organization) {
            User::withTrashed()->where('organization_id', $organization->id)->get()
                ->each(fn (User $user) => $user->forceDelete());

            $organization->forceDelete();
        });

        return response()->noContent();
    }
}

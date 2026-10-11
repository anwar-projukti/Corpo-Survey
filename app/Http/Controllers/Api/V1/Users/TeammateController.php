<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Actions\Users\CreateTeammate;
use App\Actions\Users\DeleteTeammate;
use App\Actions\Users\UpdateTeammate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreTeammateRequest;
use App\Http\Requests\Users\UpdateTeammateRequest;
use App\Http\Resources\Users\TeammateResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * An Org Admin managing their own organization's teammates (spec §13
 * "Users & Permissions") — the flow App\Actions\Fortify\CreateNewUser's own
 * doc-block defers to, since that action only ever creates a brand-new
 * organization plus its first Org Admin.
 */
class TeammateController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $teammates = User::query()
            ->where('organization_id', $request->user()->organization_id)
            ->orderBy('name')
            ->get();

        return TeammateResource::collection($teammates);
    }

    public function store(StoreTeammateRequest $request, CreateTeammate $createTeammate): TeammateResource
    {
        $teammate = $createTeammate->handle($request->user(), $request->validated());

        return new TeammateResource($teammate);
    }

    public function update(UpdateTeammateRequest $request, User $teammate, UpdateTeammate $updateTeammate): TeammateResource
    {
        $teammate = $updateTeammate->handle($request->user(), $teammate, $request->validated());

        return new TeammateResource($teammate);
    }

    public function destroy(Request $request, User $teammate, DeleteTeammate $deleteTeammate): Response
    {
        $this->authorize('delete', $teammate);

        $deleteTeammate->handle($request->user(), $teammate);

        return response()->noContent();
    }
}

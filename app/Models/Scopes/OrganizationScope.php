<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Enforces tenant isolation at the query level. The organization_id used here
 * always comes from the authenticated user's own record — never from request
 * input — so a client can never widen or redirect a query to another tenant.
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        $builder->where($model->qualifyColumn('organization_id'), $user->organization_id);
    }
}

<?php

namespace Tests\Fixtures;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * Exists only to exercise BelongsToOrganization/OrganizationScope in
 * isolation, without depending on a real tenant-scoped model from a
 * later phase (surveys, responses, ...).
 */
class TenantScopedTestModel extends Model
{
    use BelongsToOrganization;

    protected $table = 'tenant_scoped_test_models';

    protected $fillable = ['organization_id', 'name'];
}

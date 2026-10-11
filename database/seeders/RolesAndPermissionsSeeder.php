<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permission set from the spec (§5.2), unabbreviated.
     *
     * @var list<string>
     */
    private array $permissions = [
        'survey.view', 'survey.create', 'survey.edit', 'survey.delete', 'survey.publish',
        'response.view', 'response.edit', 'response.delete', 'response.export', 'response.import',
        'report.view', 'report.export',
        'user.manage', 'settings.manage',
    ];

    /**
     * Role => permissions. Super Admin is intentionally excluded here: it
     * bypasses permission checks entirely via Gate::before (see AuthServiceProvider).
     *
     * @var array<string, list<string>>
     */
    private array $rolePermissions = [
        'Org Admin' => [
            'survey.view', 'survey.create', 'survey.edit', 'survey.delete', 'survey.publish',
            'response.view', 'response.edit', 'response.delete', 'response.export', 'response.import',
            'report.view', 'report.export',
            'user.manage', 'settings.manage',
        ],
        'Editor' => [
            'survey.view', 'survey.create', 'survey.edit', 'survey.publish',
            'response.view',
            'report.view',
        ],
        'Field User' => [
            'survey.view',
            'response.view',
        ],
        'Viewer' => [
            'survey.view',
            'response.view',
            'report.view', 'report.export',
        ],
    ];

    public function run(): void
    {
        Cache::forget('spatie.permission.cache');

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        foreach ($this->rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}

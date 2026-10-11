<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
        ]);
        $superAdmin->assignRole('Super Admin');

        $organization = Organization::factory()->create([
            'name' => 'Acme Research',
            'slug' => 'acme-research',
        ]);

        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Org Admin',
            'email' => 'admin@example.com',
        ]);
        $orgAdmin->assignRole('Org Admin');

        // Seeds the /login (regular-user) portal — Super Admin and Org
        // Admin above cover /admin/login and /org/login, but neither role
        // is allowed through /login (see App\Support\LoginPortal), so
        // without this there was no account to sign in with there.
        $fieldUser = User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Field User',
            'email' => 'fielduser@example.com',
        ]);
        $fieldUser->assignRole('Field User');

        $this->call(SurveySeeder::class);
    }
}

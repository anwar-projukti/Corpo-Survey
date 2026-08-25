<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\TenantScopedTestModel;

beforeEach(function () {
    Schema::create('tenant_scoped_test_models', function (Blueprint $table) {
        $table->id();
        $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('tenant_scoped_test_models');
});

test('a Super Admin bypasses all permission checks', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('Super Admin');

    expect(Gate::forUser($superAdmin)->allows('survey.delete'))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('some.made.up.permission'))->toBeTrue();
});

test('an Org Admin has the full permission set', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');

    expect($orgAdmin->can('survey.delete'))->toBeTrue()
        ->and($orgAdmin->can('user.manage'))->toBeTrue()
        ->and($orgAdmin->can('settings.manage'))->toBeTrue();
});

test('an Editor cannot delete surveys or manage users', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    expect($editor->can('survey.create'))->toBeTrue()
        ->and($editor->can('survey.delete'))->toBeFalse()
        ->and($editor->can('user.manage'))->toBeFalse();
});

test('a Field User can only view surveys and responses', function () {
    $fieldUser = User::factory()->create();
    $fieldUser->assignRole('Field User');

    expect($fieldUser->can('survey.view'))->toBeTrue()
        ->and($fieldUser->can('response.view'))->toBeTrue()
        ->and($fieldUser->can('survey.create'))->toBeFalse()
        ->and($fieldUser->can('response.export'))->toBeFalse();
});

test('a Viewer can view and export reports but not edit anything', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('Viewer');

    expect($viewer->can('report.view'))->toBeTrue()
        ->and($viewer->can('report.export'))->toBeTrue()
        ->and($viewer->can('survey.edit'))->toBeFalse()
        ->and($viewer->can('response.edit'))->toBeFalse();
});

test('BelongsToOrganization scopes queries to the authenticated users organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->create(['organization_id' => $orgA->id]);
    $userB = User::factory()->create(['organization_id' => $orgB->id]);

    $widgetA = TenantScopedTestModel::withoutGlobalScopes()->create([
        'organization_id' => $orgA->id,
        'name' => 'belongs to org A',
    ]);
    $widgetB = TenantScopedTestModel::withoutGlobalScopes()->create([
        'organization_id' => $orgB->id,
        'name' => 'belongs to org B',
    ]);

    $this->actingAs($userA);

    $visible = TenantScopedTestModel::pluck('id');

    expect($visible)->toContain($widgetA->id)
        ->and($visible)->not->toContain($widgetB->id);
});

test('BelongsToOrganization stamps organization_id from the authenticated user on create', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($user);

    // Even if a client tried to submit a different organization_id, model
    // creation would need to explicitly honor that — this create() call
    // simulates the common case where it's simply omitted.
    $widget = TenantScopedTestModel::create(['name' => 'created by user']);

    expect($widget->organization_id)->toBe($organization->id);
});

test('a Super Admin is not restricted by the organization scope', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    TenantScopedTestModel::withoutGlobalScopes()->create(['organization_id' => $orgA->id, 'name' => 'A']);
    TenantScopedTestModel::withoutGlobalScopes()->create(['organization_id' => $orgB->id, 'name' => 'B']);

    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('Super Admin');

    $this->actingAs($superAdmin);

    expect(TenantScopedTestModel::count())->toBe(2);
});

test('a suspended organization can be identified via isActive', function () {
    $organization = Organization::factory()->suspended()->create();

    expect($organization->isActive())->toBeFalse();
});

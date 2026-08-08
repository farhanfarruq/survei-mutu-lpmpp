<?php

namespace Tests\Feature\Foundation;

use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\OrganizationalScope;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationalScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_subtree_scope_includes_descendants_and_excludes_siblings(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $root = OrganizationalUnit::factory()->create(['code' => 'ROOT']);
        $faculty = OrganizationalUnit::factory()->create(['code' => 'FAC', 'parent_id' => $root->id]);
        $program = OrganizationalUnit::factory()->create(['code' => 'PROG', 'parent_id' => $faculty->id]);
        $sibling = OrganizationalUnit::factory()->create(['code' => 'OTHER', 'parent_id' => $root->id]);
        $user = User::factory()->create();
        $user->givePermissionTo('organizational-units.view');
        $user->organizationalUnits()->attach($faculty, ['scope_mode' => 'subtree', 'is_primary' => true]);

        $ids = app(OrganizationalScope::class)->accessibleUnitIds($user);

        $this->assertTrue($ids->contains($faculty->id));
        $this->assertTrue($ids->contains($program->id));
        $this->assertFalse($ids->contains($sibling->id));

        $this->actingAs($user)->getJson("/api/v1/organizational-units/{$program->id}")->assertOk();
        $this->actingAs($user)->getJson("/api/v1/organizational-units/{$sibling->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_scope_all_permission_can_access_every_unit(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $units = OrganizationalUnit::factory()->count(3)->create();
        $user = User::factory()->create();
        $user->givePermissionTo(['organizational-units.view', 'organization.scope.all']);

        $this->assertCount(3, app(OrganizationalScope::class)->accessibleUnitIds($user));
        $this->actingAs($user)->getJson("/api/v1/organizational-units/{$units->last()->id}")->assertOk();
    }
}

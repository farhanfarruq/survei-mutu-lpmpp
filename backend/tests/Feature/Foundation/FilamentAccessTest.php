<?php

namespace Tests\Feature\Foundation;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_access_requires_active_user_and_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $panel = Filament::getPanel('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $respondent = User::factory()->create();
        $respondent->assignRole('respondent');

        $this->assertTrue($admin->canAccessPanel($panel));
        $this->assertFalse($respondent->canAccessPanel($panel));

        $admin->update(['is_active' => false]);
        $this->assertFalse($admin->canAccessPanel($panel));
    }

    public function test_super_admin_can_render_user_edit_form(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->get("/admin/users/{$target->id}/edit")
            ->assertOk();
    }

    public function test_protected_super_admin_role_cannot_be_edited_but_regular_role_can(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $protectedRole = $admin->roles()->firstOrFail();
        $regularRole = Role::findByName('admin_lpmpp');

        $this->actingAs($admin)
            ->get("/admin/roles/{$protectedRole->id}/edit")
            ->assertForbidden();

        $this->actingAs($admin)
            ->get("/admin/roles/{$regularRole->id}/edit")
            ->assertOk();
    }
}

<?php

namespace Tests\Feature\Foundation;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
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

    public function test_admin_lpmpp_cannot_access_role_management(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');
        $regularRole = Role::findByName('admin_lpmpp');

        $this->assertFalse($admin->can('roles.view'));
        $this->actingAs($admin)->get('/admin/roles')->assertForbidden();
        $this->actingAs($admin)->get('/admin/roles/create')->assertForbidden();
        $this->actingAs($admin)->get("/admin/roles/{$regularRole->id}/edit")->assertForbidden();
    }

    public function test_leader_has_read_only_filament_access_and_the_vue_shortcut(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $panel = Filament::getPanel('admin');
        $leader = User::factory()->create();
        $leader->assignRole('leader');

        $this->assertTrue($leader->canAccessPanel($panel));
        $this->assertEqualsCanonicalizing([
            'admin.panel.access',
            'system.status.view',
            'organizational-units.view',
            'users.view',
            'template.read',
            'validation.read',
            'campaign.read',
            'analysis.read',
            'report.read',
            'ai.read',
            'notification.read',
            'activity.read',
            'finding.read',
            'action.read',
            'follow-up.dashboard.read',
        ], $leader->getAllPermissions()->pluck('name')->all());

        $this->actingAs($leader)->get('/admin')->assertOk()->assertSee('Buka Dashboard Hasil Survei');
        $this->actingAs($leader)->get('/admin/surveys')->assertOk();
        $this->actingAs($leader)->get('/admin/users')->assertOk();
        $this->actingAs($leader)->get('/admin/surveys/create')->assertForbidden();
        $this->actingAs($leader)->get('/admin/users/create')->assertForbidden();
        $this->actingAs($leader)->get('/admin/buat-formulir')->assertForbidden();
        $this->actingAs($leader)->get('/admin/roles')->assertForbidden();
    }

    public function test_admin_sees_the_survey_dashboard_shortcut(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Buka Dashboard Hasil Survei');
    }

    public function test_admin_lpmpp_only_sees_the_simple_survey_navigation(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin_lpmpp');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard Mutu')
            ->assertSee('Buka Dashboard Hasil Survei')
            ->assertSee('Buat Formulir')
            ->assertSee('Formulir Saya')
            ->assertSee('Kelola Survei')
            ->assertSee('Bank Pertanyaan')
            ->assertSee('Riwayat Aktivitas')
            ->assertDontSee('Unit Organisasi')
            ->assertDontSee('Pengguna')
            ->assertDontSee('Kelompok Responden')
            ->assertDontSee('Periode Survei')
            ->assertDontSee('Template Survei');

        $this->actingAs($admin)
            ->get('/admin/surveys/create')
            ->assertOk()
            ->assertSee('Formulir yang digunakan')
            ->assertSee('Nama survei')
            ->assertSee('Siapa yang akan mengisi?')
            ->assertSee('Waktu Indonesia Barat (WIB)')
            ->assertSee('Pilih unit/program studi')
            ->assertDontSee('Kode survei')
            ->assertDontSee('Minimum reporting')
            ->assertDontSee('Privacy notice');
    }

    public function test_super_admin_keeps_system_settings_but_uses_the_simple_survey_navigation(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard Mutu')
            ->assertSee('Buka Dashboard Hasil Survei')
            ->assertSee('Buat Formulir')
            ->assertSee('Formulir Saya')
            ->assertSee('Kelola Survei')
            ->assertSee('Bank Pertanyaan')
            ->assertSee('Riwayat Aktivitas')
            ->assertSee('Unit Organisasi')
            ->assertSee('Pengguna')
            ->assertSee('Peran')
            ->assertDontSee('Permission')
            ->assertDontSee('Kelompok Responden')
            ->assertDontSee('Periode Survei')
            ->assertDontSee('Template Survei');

        $this->actingAs($admin)
            ->get('/admin/buat-formulir')
            ->assertOk()
            ->assertSee('Bangun formulir')
            ->assertSee('Skala kepuasan 1–5');
    }

    public function test_role_permissions_are_grouped_and_labeled_in_plain_indonesian(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $options = RoleResource::permissionOptions();
        $createUserId = Permission::findByName('users.create')->getKey();

        $this->assertCount(8, $options);
        $this->assertSame('Tambah pengguna', $options['Pengguna & peran'][$createUserId]);
        $this->assertArrayHasKey('Survei & responden', $options);
        $this->assertSame(Permission::count(), array_sum(array_map('count', $options)));
    }
}

<?php

namespace Tests\Feature\Foundation;

use App\Models\User;
use Database\Seeders\FoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnifiedLoginAndDemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_login_redirects_to_the_vue_login_page(): void
    {
        config(['app.frontend_url' => 'http://localhost:5173']);

        $this->get('/admin/login')
            ->assertRedirect('http://localhost:5173/login?redirect=%2Fadmin');
    }

    public function test_plain_localhost_is_supported_by_sanctum_and_cors(): void
    {
        $origin = 'http://localhost:5173';

        $request = Request::create('/api/v1/me', server: ['HTTP_ORIGIN' => $origin]);
        $this->assertTrue(EnsureFrontendRequestsAreStateful::fromFrontend($request));

        $this->withHeaders([
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/v1/auth/login')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', $origin);
    }

    public function test_foundation_seeder_preserves_existing_accounts_and_provides_every_required_role(): void
    {
        foreach ([
            'reviewer@example.test' => 'reviewer',
            'analis@example.test' => 'analyst',
            'pic@example.test' => 'pic',
            'verifier@example.test' => 'verifier',
        ] as $email => $role) {
            Role::findOrCreate($role);
            User::factory()->create(['email' => $email])->assignRole($role);
        }

        $this->seed(FoundationSeeder::class);

        $accounts = [
            'superadmin@example.test' => 'super_admin',
            'admin.lpmpp@example.test' => 'admin_lpmpp',
            'pimpinan@example.test' => 'leader',
            'responden@example.test' => 'respondent',
        ];

        foreach ($accounts as $email => $role) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertTrue($user->hasRole($role), "Role {$role} tidak ditemukan untuk {$email}.");
            $this->assertTrue(Hash::check('password', $user->password), "Password default salah untuk {$email}.");
            $this->assertTrue($user->is_active);
            $this->assertMatchesRegularExpression('/^[0-9]+$/', (string) $user->identity_number);
            $this->assertCount(1, $user->organizationalUnits);
            $this->assertSame(in_array($role, ['super_admin', 'admin_lpmpp', 'leader'], true), $user->can('admin.panel.access'));
        }

        $this->assertSame(
            ['admin_lpmpp', 'leader', 'respondent', 'super_admin'],
            Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->all(),
        );
        $this->assertSame(47, Role::findByName('admin_lpmpp')->permissions()->count());
        $this->assertTrue(Role::findByName('admin_lpmpp')->hasPermissionTo('organization.scope.all'));
        $this->assertFalse(Role::findByName('admin_lpmpp')->hasPermissionTo('roles.view'));
        $this->assertTrue(Role::findByName('admin_lpmpp')->hasPermissionTo('action.verify'));
        $this->assertDatabaseHas('users', ['email' => 'reviewer@example.test']);
        $this->assertDatabaseHas('users', ['email' => 'analis@example.test']);
        $this->assertDatabaseHas('users', ['email' => 'pic@example.test']);
        $this->assertDatabaseHas('users', ['email' => 'verifier@example.test']);
    }
}

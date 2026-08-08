<?php

namespace Tests\Feature\Foundation;

use App\Models\OrganizationalUnit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_active_user_can_login_read_session_profile_and_logout(): void
    {
        $unit = OrganizationalUnit::factory()->create();
        $user = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => Hash::make('ValidPassphrase!123'),
        ]);
        $user->assignRole('admin_lpmpp');
        $user->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.test',
            'password' => 'ValidPassphrase!123',
        ])->assertOk();

        $this->assertAuthenticatedAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->public_id)
            ->assertJsonPath('data.roles.0', 'admin_lpmpp')
            ->assertJsonMissingPath('data.password');

        $this->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertGuest();
    }

    public function test_inactive_user_receives_generic_validation_problem(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => Hash::make('ValidPassphrase!123'),
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.test',
            'password' => 'ValidPassphrase!123',
        ])->assertUnprocessable()
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonMissingPath('password');
    }

    public function test_authenticated_api_login_is_idempotent_without_cross_origin_redirect(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
                'Origin' => 'http://localhost:5173',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'irrelevant-while-authenticated',
            ])
            ->assertNoContent()
            ->assertHeaderMissing('Location')
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');

        $this->assertAuthenticatedAs($user);
    }

    public function test_unauthenticated_api_uses_problem_details(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('code', 'unauthenticated')
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'instance', 'code', 'request_id']);
    }
}

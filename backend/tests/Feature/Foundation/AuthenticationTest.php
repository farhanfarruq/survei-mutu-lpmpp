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
            'identity_number' => 'DOSEN-001',
            'password' => Hash::make('ValidPassphrase!123'),
        ]);
        $user->assignRole('admin_lpmpp');
        $user->organizationalUnits()->attach($unit, ['scope_mode' => 'self', 'is_primary' => true]);

        $this->postJson('/api/v1/auth/login', [
            'identity_number' => 'dosen-001',
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
            'identity_number' => 'MHS-001',
            'password' => Hash::make('ValidPassphrase!123'),
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'identity_number' => 'MHS-001',
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
                'identity_number' => $user->email,
                'password' => 'irrelevant-while-authenticated',
            ])
            ->assertNoContent()
            ->assertHeaderMissing('Location')
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');

        $this->assertAuthenticatedAs($user);
    }

    public function test_public_registration_creates_only_an_active_respondent_in_the_selected_program(): void
    {
        $faculty = OrganizationalUnit::factory()->create(['type' => 'faculty', 'is_active' => true]);
        $program = OrganizationalUnit::factory()->create([
            'parent_id' => $faculty->id,
            'type' => 'program',
            'is_active' => true,
        ]);
        $inactiveProgram = OrganizationalUnit::factory()->create(['type' => 'program', 'is_active' => false]);

        $this->getJson('/api/v1/auth/registration-options')
            ->assertOk()
            ->assertJsonPath('data.programs.0.id', $program->id)
            ->assertJsonPath('data.programs.0.faculty_name', $faculty->name)
            ->assertJsonMissing(['id' => $inactiveProgram->id]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Mahasiswa Baru',
            'account_type' => 'student',
            'identity_number' => ' 20260001 ',
            'organizational_unit_id' => $program->id,
            'password' => 'ValidPassphrase!123',
            'password_confirmation' => 'ValidPassphrase!123',
        ])->assertCreated()
            ->assertJsonPath('data.identity_number', '20260001')
            ->assertJsonPath('data.roles.0', 'respondent')
            ->assertJsonMissingPath('data.email');

        $user = User::query()->where('identity_number', '20260001')->firstOrFail();
        $this->assertGuest();

        $this->postJson('/api/v1/auth/login', [
            'identity_number' => '20260001',
            'password' => 'ValidPassphrase!123',
        ])->assertOk();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('respondent'));
        $this->assertSame(['respondent'], $user->roles->pluck('name')->all());
        $this->assertDatabaseHas('organizational_unit_user', [
            'user_id' => $user->id,
            'organizational_unit_id' => $program->id,
            'scope_mode' => 'self',
            'is_primary' => true,
        ]);
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

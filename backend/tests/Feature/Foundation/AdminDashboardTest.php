<?php

namespace Tests\Feature\Foundation;

use App\Models\AggregateSnapshot;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_seeders_are_idempotent_and_render_aggregate_quality_information(): void
    {
        $existingAccount = User::factory()->create([
            'name' => 'Akun Tetap',
            'email' => 'tetap@example.test',
            'password' => Hash::make('rahasia-tetap'),
        ]);
        $originalId = $existingAccount->id;
        $originalPassword = $existingAccount->password;

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $existingAccount->refresh();
        $this->assertSame($originalId, $existingAccount->id);
        $this->assertSame('Akun Tetap', $existingAccount->name);
        $this->assertSame($originalPassword, $existingAccount->password);
        $this->assertDatabaseCount('users', 5);
        $this->assertTrue($existingAccount->organizationalUnits()->where('code', 'ITDA')->exists());

        $this->assertDatabaseCount('organizational_units', 24);
        $this->assertSame(2, OrganizationalUnit::query()->where('type', 'faculty')->count());
        $this->assertSame(6, OrganizationalUnit::query()->where('type', 'program')->count());
        $this->assertDatabaseHas('organizational_units', ['code' => 'ITDA', 'name' => 'Institut Teknologi Dirgantara Adisutjipto']);
        $this->assertDatabaseHas('organizational_units', ['code' => 'PRODI-AE', 'name' => 'D3 Aeronautika']);
        $this->assertDatabaseMissing('organizational_units', ['code' => 'UNIV-DEMO']);
        $this->assertDatabaseCount('categories', 4);
        $this->assertDatabaseCount('questions', 4);
        $this->assertDatabaseCount('surveys', 6);
        $this->assertDatabaseCount('aggregate_snapshots', 4);
        $this->assertDatabaseCount('findings', 4);
        $this->assertDatabaseCount('follow_up_actions', 4);
        $this->assertDatabaseCount('survey_responses', 0);
        $this->assertSame(82.4, (float) data_get(AggregateSnapshot::query()->latest('generated_at')->firstOrFail()->metrics, 'overall.normalized_score'));

        $admin = User::query()->where('email', 'admin.lpmpp@example.test')->firstOrFail();
        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSeeText('Dashboard Mutu')
            ->assertSeeText('Ringkasan periode berjalan')
            ->assertSeeText('Partisipasi per survei')
            ->assertSeeText('Tren indeks mutu')
            ->assertSeeText('Capaian per kategori')
            ->assertSeeText('Temuan yang perlu perhatian')
            ->assertDontSeeText('Redis')
            ->assertDontSeeText('Horizon worker terpisah')
            ->assertDontSeeText('Terhubung');
    }
}

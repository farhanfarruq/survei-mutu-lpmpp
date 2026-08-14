<?php

namespace Database\Seeders;

use App\Models\InstrumentVersion;
use App\Models\OrganizationalUnit;
use App\Models\RespondentGroup;
use App\Models\Survey;
use App\Models\SurveyPeriod;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class DashboardSurveySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Data dashboard fiktif tidak boleh dibuat di production.');
        }

        $admin = User::role('admin_lpmpp')->firstOrFail();
        $approver = User::role('super_admin')->firstOrFail();
        $lpmpp = OrganizationalUnit::query()->where('code', 'LPMPP')->firstOrFail();
        $engineering = OrganizationalUnit::query()->where('code', 'FTK')->firstOrFail();
        $industry = OrganizationalUnit::query()->where('code', 'FTI')->firstOrFail();
        $instrument = InstrumentVersion::query()
            ->whereHas('template', fn ($query) => $query->where('code', 'TPL-MUTU-LAYANAN-ITDA'))
            ->firstOrFail();

        $engineeringGroup = RespondentGroup::query()->where('code', 'RESPONDEN-FTK')->firstOrFail();
        $industryGroup = RespondentGroup::query()->updateOrCreate(
            ['code' => 'RESPONDEN-FTI'],
            ['organizational_unit_id' => $industry->id, 'name' => 'Responden Fakultas Teknologi Industri', 'source_type' => 'manual', 'schema_version' => 'v1', 'is_active' => true],
        );

        $campaigns = [
            ['period' => ['2024-GANJIL', 'Semester Ganjil 2024/2025', '2024-08-01', '2025-01-31'], 'code' => 'SRV-MUTU-ITDA-2024-GANJIL', 'name' => 'Survei Mutu Layanan ITDA Semester Ganjil 2024/2025', 'opens' => '2024-10-01 00:00:00+07', 'closes' => '2024-10-31 23:59:59+07', 'responses' => 312, 'eligible' => 420, 'group' => $engineeringGroup, 'unit' => $engineering],
            ['period' => ['2025-GENAP', 'Semester Genap 2024/2025', '2025-02-01', '2025-07-31'], 'code' => 'SRV-MUTU-ITDA-2025-GENAP', 'name' => 'Survei Mutu Layanan ITDA Semester Genap 2024/2025', 'opens' => '2025-04-01 00:00:00+07', 'closes' => '2025-04-30 23:59:59+07', 'responses' => 356, 'eligible' => 440, 'group' => $industryGroup, 'unit' => $industry],
            ['period' => ['2025-GANJIL', 'Semester Ganjil 2025/2026', '2025-08-01', '2026-01-31'], 'code' => 'SRV-MUTU-ITDA-2025-GANJIL', 'name' => 'Survei Mutu Layanan ITDA Semester Ganjil 2025/2026', 'opens' => '2025-10-01 00:00:00+07', 'closes' => '2025-10-31 23:59:59+07', 'responses' => 401, 'eligible' => 480, 'group' => $engineeringGroup, 'unit' => $engineering],
            ['period' => ['2026-GENAP', 'Semester Genap 2025/2026', '2026-02-01', '2026-07-31'], 'code' => 'SRV-MUTU-ITDA-2026-GENAP', 'name' => 'Survei Mutu Layanan ITDA Semester Genap 2025/2026', 'opens' => '2026-05-01 00:00:00+07', 'closes' => '2026-05-31 23:59:59+07', 'responses' => 448, 'eligible' => 510, 'group' => $industryGroup, 'unit' => $industry],
        ];

        foreach ($campaigns as $campaign) {
            [$periodCode, $periodName, $startsOn, $endsOn] = $campaign['period'];
            $period = SurveyPeriod::query()->updateOrCreate(
                ['code' => $periodCode],
                ['name' => $periodName, 'starts_on' => $startsOn, 'ends_on' => $endsOn, 'timezone' => 'Asia/Jakarta', 'status' => 'closed'],
            );
            $survey = Survey::query()->firstOrCreate(
                ['code' => $campaign['code']],
                [
                    'instrument_version_id' => $instrument->id,
                    'survey_period_id' => $period->id,
                    'owner_unit_id' => $lpmpp->id,
                    'name' => $campaign['name'],
                    'state' => 'draft',
                    'privacy_mode' => 'anonymous',
                    'opens_at' => $campaign['opens'],
                    'closes_at' => $campaign['closes'],
                    'timezone' => 'Asia/Jakarta',
                    'privacy_notice' => 'Data simulasi ITDA. Jawaban disimpan anonim dan hanya ditampilkan dalam bentuk agregat.',
                    'reporting_threshold' => 10,
                    'action_owner_id' => $admin->id,
                    'policy_snapshot' => ['anonymous' => true, 'reporting_threshold' => 10],
                    'population_snapshot_hash' => hash('sha256', $campaign['code'].'-population'),
                    'responses_count' => 0,
                    'created_by' => $admin->id,
                    'submitted_by' => $admin->id,
                    'submitted_at' => $campaign['opens'],
                    'approved_by' => $approver->id,
                    'approved_at' => $campaign['opens'],
                    'published_at' => $campaign['opens'],
                    'review_note' => 'Instrumen dan kebijakan privasi telah diperiksa.',
                ],
            );
            if ($survey->wasRecentlyCreated) {
                $survey->targets()->create([
                    'target_type' => 'respondent_group',
                    'respondent_group_id' => $campaign['group']->id,
                    'target_unit_id' => $campaign['unit']->id,
                    'eligible_count' => $campaign['eligible'],
                    'sampling' => ['method' => 'census'],
                    'frame_checksum' => hash('sha256', $campaign['code'].'-frame'),
                ]);
                $survey->update(['state' => 'closed', 'responses_count' => $campaign['responses'], 'closed_at' => $campaign['closes']]);
            }
        }

        $currentPeriod = SurveyPeriod::query()->where('code', '2026-GANJIL')->firstOrFail();
        $active = Survey::query()->firstOrCreate(
            ['code' => 'SRV-DIGITAL-ITDA-2026'],
            [
                'instrument_version_id' => $instrument->id,
                'survey_period_id' => $currentPeriod->id,
                'owner_unit_id' => $lpmpp->id,
                'name' => 'Survei Kepuasan Layanan Digital ITDA 2026',
                'state' => 'draft',
                'privacy_mode' => 'anonymous',
                'opens_at' => '2026-08-01 00:00:00+07',
                'closes_at' => '2026-08-31 23:59:59+07',
                'timezone' => 'Asia/Jakarta',
                'privacy_notice' => 'Data simulasi ITDA. Jawaban disimpan anonim dan dilaporkan setelah memenuhi ambang minimum.',
                'reporting_threshold' => 10,
                'action_owner_id' => $admin->id,
                'policy_snapshot' => ['anonymous' => true, 'reporting_threshold' => 10],
                'population_snapshot_hash' => hash('sha256', 'SRV-DIGITAL-ITDA-2026-population'),
                'responses_count' => 0,
                'created_by' => $admin->id,
                'submitted_by' => $admin->id,
                'submitted_at' => '2026-07-20 09:00:00+07',
                'approved_by' => $approver->id,
                'approved_at' => '2026-07-22 09:00:00+07',
                'published_at' => '2026-08-01 00:00:00+07',
                'review_note' => 'Siap dipublikasikan setelah pemeriksaan target dan privasi.',
            ],
        );
        if ($active->wasRecentlyCreated) {
            $active->targets()->create(['target_type' => 'respondent_group', 'respondent_group_id' => $engineeringGroup->id, 'target_unit_id' => $engineering->id, 'eligible_count' => 520, 'sampling' => ['method' => 'census'], 'frame_checksum' => hash('sha256', 'SRV-DIGITAL-ITDA-2026-frame')]);
            $active->update(['state' => 'active', 'responses_count' => 318]);
        }

        $scheduled = Survey::query()->where('code', 'SRV-MUTU-ITDA-2026-GANJIL')->firstOrFail();
        if ($scheduled->state->configurationEditable()) {
            $scheduled->targets()->firstOrFail()->update(['eligible_count' => 540, 'target_unit_id' => $engineering->id, 'frame_checksum' => hash('sha256', 'SRV-MUTU-ITDA-2026-GANJIL-frame')]);
            $scheduled->update(['state' => 'scheduled', 'responses_count' => 0, 'approved_by' => $approver->id, 'approved_at' => '2026-07-28 09:00:00+07', 'review_note' => 'Terjadwal untuk periode berikutnya.']);
        }
    }
}

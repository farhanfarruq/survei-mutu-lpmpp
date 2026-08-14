<?php

namespace Database\Seeders;

use App\Models\AggregateSnapshot;
use App\Models\Finding;
use App\Models\FollowUpAction;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class DashboardFollowUpSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Data tindak lanjut fiktif tidak boleh dibuat di production.');
        }

        $admin = User::role('admin_lpmpp')->firstOrFail();
        $verifier = User::role('super_admin')->firstOrFail();
        $snapshot = AggregateSnapshot::query()->where('state', 'released')->latest('generated_at')->firstOrFail();
        $items = [
            ['code' => 'TM-ITDA-001', 'indicator' => 'KECEPATAN-01', 'score' => 68.0, 'title' => 'Waktu penyelesaian keluhan belum konsisten', 'severity' => 'critical', 'finding_state' => 'in_progress', 'finding_due' => now()->subDays(7), 'action' => 'Standarisasi SLA penanganan keluhan', 'action_state' => 'in_progress', 'progress' => 45, 'action_due' => now()->subDays(3)],
            ['code' => 'TM-ITDA-002', 'indicator' => 'DIGITAL-01', 'score' => 72.0, 'title' => 'Ketersediaan layanan digital perlu ditingkatkan', 'severity' => 'high', 'finding_state' => 'in_progress', 'finding_due' => now()->addDays(10), 'action' => 'Perbaikan monitoring layanan digital', 'action_state' => 'pending_verification', 'progress' => 100, 'action_due' => now()->addDays(5)],
            ['code' => 'TM-ITDA-003', 'indicator' => 'KEJELASAN-01', 'score' => 76.0, 'title' => 'Informasi alur layanan belum seragam', 'severity' => 'medium', 'finding_state' => 'verified', 'finding_due' => now()->subDays(20), 'action' => 'Publikasi ulang panduan layanan terpadu', 'action_state' => 'verified', 'progress' => 100, 'action_due' => now()->subDays(12)],
            ['code' => 'TM-ITDA-004', 'indicator' => 'KEANDALAN-01', 'score' => 74.0, 'title' => 'Konsistensi jam layanan antarunit', 'severity' => 'high', 'finding_state' => 'open', 'finding_due' => now()->addDays(21), 'action' => 'Penyelarasan jadwal dan standar layanan', 'action_state' => 'assigned', 'progress' => 0, 'action_due' => now()->addDays(14)],
        ];

        foreach ($items as $item) {
            $finding = Finding::query()->updateOrCreate(
                ['code' => $item['code']],
                [
                    'aggregate_snapshot_id' => $snapshot->id,
                    'owner_unit_id' => $snapshot->owner_unit_id,
                    'source_type' => 'low_indicator',
                    'source_indicator_code' => $item['indicator'],
                    'source_score' => $item['score'],
                    'title' => $item['title'],
                    'description' => 'Temuan berasal dari indikator agregat yang berada di bawah sasaran mutu internal.',
                    'source_evidence' => 'Snapshot agregat released '.$snapshot->id.'.',
                    'severity' => $item['severity'],
                    'state' => $item['finding_state'],
                    'due_on' => $item['finding_due'],
                    'created_by' => $admin->id,
                    'resource_version' => 1,
                ],
            );
            FollowUpAction::query()->updateOrCreate(
                ['finding_id' => $finding->id, 'title' => $item['action']],
                [
                    'pic_user_id' => $admin->id,
                    'verifier_user_id' => $verifier->id,
                    'root_cause' => 'Standar proses belum diterapkan konsisten pada seluruh titik layanan.',
                    'plan' => 'Tetapkan standar, komunikasikan kepada unit, lalu pantau penerapannya secara berkala.',
                    'expected_output' => 'Standar layanan terdokumentasi dan capaian indikator meningkat pada periode berikutnya.',
                    'resource_needs' => 'Koordinasi lintas unit dan dukungan sistem informasi.',
                    'assignment_note' => 'Prioritas berdasarkan tingkat keparahan dan tenggat.',
                    'state' => $item['action_state'],
                    'progress' => $item['progress'],
                    'due_on' => $item['action_due'],
                    'revision_count' => 0,
                    'accepted_at' => $item['action_state'] === 'assigned' ? null : now()->subDays(14),
                    'submitted_at' => in_array($item['action_state'], ['pending_verification', 'verified'], true) ? now()->subDays(2) : null,
                    'verified_at' => $item['action_state'] === 'verified' ? now()->subDay() : null,
                    'resource_version' => 1,
                ],
            );
        }
    }
}

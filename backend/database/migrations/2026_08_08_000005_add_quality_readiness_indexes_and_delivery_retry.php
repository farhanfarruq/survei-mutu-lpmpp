<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestampTz('last_attempt_at')->nullable();
            $table->index(['state', 'last_attempt_at']);
        });
        Schema::table('survey_participations', fn (Blueprint $table) => $table->index(
            ['survey_id', 'completed_at', 'declined_at', 'invitation_revoked_at', 'last_reminded_at'],
            'survey_participations_reminder_eligibility_index'
        ));
        Schema::table('aggregate_snapshots', fn (Blueprint $table) => $table->index(['state', 'owner_unit_id', 'generated_at']));
        Schema::table('report_exports', fn (Blueprint $table) => $table->index(['requested_by', 'state', 'expires_at']));
    }

    public function down(): void
    {
        Schema::table('report_exports', fn (Blueprint $table) => $table->dropIndex(['requested_by', 'state', 'expires_at']));
        Schema::table('aggregate_snapshots', fn (Blueprint $table) => $table->dropIndex(['state', 'owner_unit_id', 'generated_at']));
        Schema::table('survey_participations', fn (Blueprint $table) => $table->dropIndex('survey_participations_reminder_eligibility_index'));
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->dropIndex(['state', 'last_attempt_at']);
            $table->dropColumn(['attempt_count', 'last_attempt_at']);
        });
    }
};

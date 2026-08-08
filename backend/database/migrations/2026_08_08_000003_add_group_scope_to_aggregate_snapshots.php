<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aggregate_snapshots', function (Blueprint $table): void {
            $table->foreignUuid('respondent_group_id')->nullable()->after('survey_period_id')->constrained('respondent_groups')->nullOnDelete();
            $table->index(['state', 'respondent_group_id']);
        });
    }

    public function down(): void
    {
        Schema::table('aggregate_snapshots', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('respondent_group_id');
        });
    }
};

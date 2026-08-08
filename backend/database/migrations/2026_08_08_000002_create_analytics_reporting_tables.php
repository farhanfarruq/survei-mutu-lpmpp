<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('state', 24)->default('queued');
            $table->string('input_hash', 64);
            $table->string('formula_version', 40)->default('methodology-v1');
            $table->json('parameters')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestampsTz();
            $table->index(['survey_id', 'input_hash', 'state']);
        });

        Schema::create('aggregate_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('analysis_run_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('owner_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->foreignUuid('survey_period_id')->nullable()->constrained('survey_periods')->nullOnDelete();
            $table->string('state', 24)->default('candidate');
            $table->json('metrics');
            $table->json('filter_provenance');
            $table->json('limitations')->nullable();
            $table->unsignedInteger('response_count');
            $table->unsignedInteger('eligible_count');
            $table->unsignedInteger('reporting_threshold');
            $table->boolean('suppressed')->default(false);
            $table->string('checksum', 64);
            $table->timestampTz('generated_at');
            $table->timestampTz('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['state', 'owner_unit_id', 'survey_period_id']);
        });

        Schema::create('report_exports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('aggregate_snapshot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('state', 24)->default('queued');
            $table->string('format', 12);
            $table->json('filters')->nullable();
            $table->json('filter_provenance');
            $table->string('idempotency_key_hash', 64);
            $table->string('disk', 24)->default('local');
            $table->string('path')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('downloaded_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestampsTz();
            $table->unique(['requested_by', 'idempotency_key_hash']);
        });

        Schema::create('report_download_tickets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_export_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_download_tickets');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('aggregate_snapshots');
        Schema::dropIfExists('analysis_runs');
    }
};

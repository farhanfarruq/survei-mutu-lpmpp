<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_configs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider', 40);
            $table->string('model', 120);
            $table->string('base_url', 500);
            $table->text('secret_ciphertext')->nullable();
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('max_input_tokens')->default(4000);
            $table->unsignedInteger('max_output_tokens')->default(1000);
            $table->unsignedBigInteger('max_cost_micros')->default(500000);
            $table->unsignedInteger('input_cost_micros_per_1k')->default(0);
            $table->unsignedInteger('output_cost_micros_per_1k')->default(0);
            $table->unsignedSmallInteger('timeout_seconds')->default(30);
            $table->unsignedSmallInteger('rate_limit_per_minute')->default(10);
            $table->string('connection_status', 24)->default('untested');
            $table->timestampTz('last_tested_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['provider', 'model']);
        });

        Schema::create('ai_prompt_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('use_case', 64);
            $table->unsignedInteger('version');
            $table->text('system_prompt');
            $table->json('output_schema');
            $table->boolean('active')->default(false);
            $table->char('checksum', 64);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['use_case', 'version']);
        });

        Schema::create('ai_jobs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('analysis_run_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('aggregate_snapshot_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('ai_provider_config_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('ai_prompt_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('use_case', 64)->default('comprehensive_insight');
            $table->string('state', 32)->default('queued');
            $table->json('source_scope');
            $table->char('input_checksum', 64);
            $table->unsignedInteger('resource_version')->default(1);
            $table->string('failure_code', 80)->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->index(['state', 'created_at']);
        });

        Schema::create('ai_results', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('ai_job_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('content');
            $table->json('edited_content')->nullable();
            $table->string('label', 120);
            $table->json('source_scope');
            $table->string('provider', 40);
            $table->string('model', 120);
            $table->string('review_status', 24)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestampTz('generated_at');
            $table->timestampTz('reviewed_at')->nullable();
            $table->unsignedInteger('resource_version')->default(1);
            $table->timestampsTz();
        });

        Schema::create('ai_usage_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('ai_job_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 40);
            $table->string('model', 120);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedBigInteger('cost_micros')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('outcome', 32);
            $table->timestampsTz();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 64);
            $table->string('channel', 24);
            $table->char('logical_key', 64);
            $table->string('state', 24);
            $table->string('provider_reference', 160)->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'channel', 'logical_key']);
        });

        Schema::create('findings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('aggregate_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('owner_unit_id')->constrained('organizational_units')->restrictOnDelete();
            $table->string('code', 80)->unique();
            $table->string('source_type', 24);
            $table->string('source_indicator_code', 80)->nullable();
            $table->decimal('source_score', 12, 6)->nullable();
            $table->string('title', 300);
            $table->text('description');
            $table->text('source_evidence');
            $table->string('severity', 24);
            $table->string('state', 32)->default('open');
            $table->date('due_on');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('resource_version')->default(1);
            $table->timestampsTz();
            $table->index(['owner_unit_id', 'state', 'due_on']);
        });

        Schema::create('follow_up_actions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('finding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pic_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('verifier_user_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 300);
            $table->text('root_cause');
            $table->text('plan');
            $table->text('expected_output');
            $table->text('resource_needs')->nullable();
            $table->text('assignment_note')->nullable();
            $table->string('state', 32)->default('assigned');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->date('due_on');
            $table->unsignedSmallInteger('revision_count')->default(0);
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->unsignedInteger('resource_version')->default(1);
            $table->timestampsTz();
            $table->index(['pic_user_id', 'state', 'due_on']);
        });

        Schema::create('action_evidence', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('follow_up_action_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 240);
            $table->text('description');
            $table->string('reference_url', 1000)->nullable();
            $table->char('checksum', 64);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
        });

        Schema::create('action_verifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('follow_up_action_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $table->string('decision', 24);
            $table->text('reason');
            $table->text('evidence_review');
            $table->unsignedSmallInteger('revision_number')->default(0);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_verifications');
        Schema::dropIfExists('action_evidence');
        Schema::dropIfExists('follow_up_actions');
        Schema::dropIfExists('findings');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_results');
        Schema::dropIfExists('ai_jobs');
        Schema::dropIfExists('ai_prompt_templates');
        Schema::dropIfExists('ai_provider_configs');
    }
};

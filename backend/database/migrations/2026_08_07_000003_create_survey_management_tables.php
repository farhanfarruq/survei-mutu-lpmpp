<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_unit_id')->constrained('organizational_units')->restrictOnDelete();
            $table->string('code', 80)->unique();
            $table->string('family_code', 80);
            $table->string('name', 240);
            $table->string('status', 24)->default('active');
            $table->text('purpose');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('retired_at')->nullable();
            $table->timestampsTz();
            $table->index(['owner_unit_id', 'status']);
        });

        Schema::create('instrument_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('major')->default(1);
            $table->unsignedSmallInteger('minor')->default(0);
            $table->unsignedSmallInteger('patch')->default(0);
            $table->string('status', 24)->default('draft');
            $table->string('comparability_status', 24)->default('pending');
            $table->text('change_reason');
            $table->char('content_hash', 64)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestampsTz();
            $table->unique(['survey_template_id', 'major', 'minor', 'patch'], 'instrument_version_semver_unique');
            $table->index(['status', 'approved_at']);
        });

        Schema::create('question_bank_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_unit_id')->constrained('organizational_units')->restrictOnDelete();
            $table->string('code', 80)->unique();
            $table->string('family_code', 80);
            $table->string('method', 40)->default('internal');
            $table->string('category_label', 200);
            $table->string('indicator_label', 200);
            $table->text('item_text');
            $table->string('response_type', 32);
            $table->text('help_text')->nullable();
            $table->text('measurement_purpose');
            $table->json('default_options')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['owner_unit_id', 'family_code', 'is_active']);
        });

        Schema::create('instrument_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instrument_version_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('title', 240);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position');
            $table->json('branch_rule')->nullable();
            $table->timestampsTz();
            $table->unique(['instrument_version_id', 'code']);
            $table->unique(['instrument_version_id', 'position']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instrument_version_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestampsTz();
            $table->unique(['instrument_version_id', 'code']);
            $table->unique(['instrument_version_id', 'position']);
        });

        Schema::create('indicators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name', 200);
            $table->string('construct', 160);
            $table->decimal('weight', 12, 6)->default(1);
            $table->text('interpretation')->nullable();
            $table->timestampsTz();
            $table->unique(['category_id', 'code']);
        });

        Schema::create('scales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instrument_version_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name', 200);
            $table->string('scale_type', 32);
            $table->decimal('min_value', 12, 6)->nullable();
            $table->decimal('max_value', 12, 6)->nullable();
            $table->boolean('na_allowed')->default(false);
            $table->string('missing_policy', 32)->default('exclude_item');
            $table->timestampsTz();
            $table->unique(['instrument_version_id', 'code']);
        });

        Schema::create('scale_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('scale_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->decimal('numeric_value', 12, 6)->nullable();
            $table->string('label', 200);
            $table->unsignedSmallInteger('position');
            $table->boolean('is_na')->default(false);
            $table->boolean('is_neutral')->default(false);
            $table->timestampsTz();
            $table->unique(['scale_id', 'code']);
            $table->unique(['scale_id', 'position']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('section_id')->constrained('instrument_sections')->cascadeOnDelete();
            $table->foreignUuid('indicator_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('scale_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignUuid('question_bank_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 80);
            $table->text('item_text');
            $table->string('response_type', 32);
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('position');
            $table->text('help_text')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('branch_rule')->nullable();
            $table->text('measurement_purpose');
            $table->string('method', 40)->default('internal');
            $table->string('pair_code', 80)->nullable();
            $table->timestampsTz();
            $table->unique(['section_id', 'code']);
            $table->unique(['section_id', 'position']);
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('label', 300);
            $table->unsignedSmallInteger('position');
            $table->decimal('score_value', 12, 6)->nullable();
            $table->boolean('is_exclusive')->default(false);
            $table->timestampsTz();
            $table->unique(['question_id', 'code']);
            $table->unique(['question_id', 'position']);
        });

        Schema::create('survey_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 80)->unique();
            $table->string('name', 200);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->string('status', 24)->default('active');
            $table->timestampsTz();
        });

        Schema::create('respondent_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organizational_unit_id')->constrained()->restrictOnDelete();
            $table->string('code', 80)->unique();
            $table->string('name', 200);
            $table->string('source_type', 24)->default('manual');
            $table->string('schema_version', 40)->default('v1');
            $table->json('filter_definition')->nullable();
            $table->char('source_snapshot_hash', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->index(['organizational_unit_id', 'is_active']);
        });

        Schema::create('surveys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instrument_version_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('survey_period_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('owner_unit_id')->constrained('organizational_units')->restrictOnDelete();
            $table->string('code', 80)->unique();
            $table->string('name', 240);
            $table->string('state', 32)->default('draft');
            $table->string('privacy_mode', 32)->default('anonymous');
            $table->timestampTz('opens_at');
            $table->timestampTz('closes_at');
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->text('privacy_notice');
            $table->unsignedSmallInteger('reporting_threshold')->default(10);
            $table->foreignId('action_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('policy_snapshot')->nullable();
            $table->char('population_snapshot_hash', 64)->nullable();
            $table->unsignedInteger('responses_count')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestampsTz();
            $table->index(['owner_unit_id', 'state']);
            $table->index(['state', 'opens_at', 'closes_at']);
        });

        Schema::create('survey_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('respondent_group_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignUuid('target_unit_id')->nullable()->constrained('organizational_units')->restrictOnDelete();
            $table->string('target_type', 24);
            $table->unsignedInteger('eligible_count')->default(0);
            $table->json('sampling')->nullable();
            $table->char('frame_checksum', 64)->nullable();
            $table->timestampsTz();
            $table->index(['survey_id', 'target_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_targets');
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('respondent_groups');
        Schema::dropIfExists('survey_periods');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('scale_points');
        Schema::dropIfExists('scales');
        Schema::dropIfExists('indicators');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('instrument_sections');
        Schema::dropIfExists('question_bank_entries');
        Schema::dropIfExists('instrument_versions');
        Schema::dropIfExists('survey_templates');
    }
};

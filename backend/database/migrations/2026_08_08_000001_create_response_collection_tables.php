<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_participations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->char('external_reference_hash', 64)->nullable();
            $table->char('invitation_token_hash', 64)->nullable()->unique();
            $table->char('completion_token_hash', 64)->nullable()->unique();
            $table->timestampTz('invitation_expires_at')->nullable();
            $table->timestampTz('invitation_revoked_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('declined_at')->nullable();
            $table->timestampTz('last_reminded_at')->nullable();
            $table->unsignedTinyInteger('reminder_count')->default(0);
            $table->timestampsTz();
            $table->unique(['survey_id', 'user_id']);
            $table->unique(['survey_id', 'external_reference_hash']);
            $table->index(['survey_id', 'completed_at', 'declined_at']);
        });

        // Content access sessions intentionally contain no user, invitation, or participation key.
        Schema::create('respondent_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestampTz('expires_at');
            $table->timestampsTz();
        });

        Schema::create('survey_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('respondent_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('state', 16)->default('started');
            $table->unsignedInteger('resource_version')->default(1);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->char('consent_version', 64);
            $table->timestampTz('consented_at');
            $table->timestampTz('submitted_at')->nullable();
            $table->string('receipt_code', 40)->nullable()->unique();
            $table->timestampsTz();
            $table->index(['survey_id', 'state']);
        });

        Schema::create('response_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_response_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained()->restrictOnDelete();
            $table->json('value');
            $table->timestampsTz();
            $table->unique(['survey_response_id', 'question_id']);
        });

        // Present only for explicitly confidential campaigns; anonymous campaigns never write here.
        Schema::create('confidential_response_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_response_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('survey_participation_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestampsTz();
        });

        Schema::create('response_idempotency_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_response_id')->constrained()->cascadeOnDelete();
            $table->string('operation', 16);
            $table->char('key_hash', 64);
            $table->char('request_fingerprint', 64);
            $table->unsignedInteger('result_version');
            $table->string('receipt_code', 40)->nullable();
            $table->timestampsTz();
            $table->unique(['survey_response_id', 'operation', 'key_hash'], 'response_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_idempotency_keys');
        Schema::dropIfExists('confidential_response_links');
        Schema::dropIfExists('response_answers');
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('respondent_sessions');
        Schema::dropIfExists('survey_participations');
    }
};

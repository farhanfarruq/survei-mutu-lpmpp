<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_jobs', function (Blueprint $table): void {
            $table->foreignId('reviewer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('ai_jobs')->whereNull('reviewer_id')->update(['reviewer_id' => DB::raw('requested_by')]);

        Schema::table('ai_jobs', function (Blueprint $table): void {
            $table->foreignId('reviewer_id')->nullable(false)->change();
        });
    }
};

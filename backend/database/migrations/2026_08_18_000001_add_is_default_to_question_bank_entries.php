<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_bank_entries', function (Blueprint $table): void {
            $table->boolean('is_default')->default(false)->after('is_active');
            $table->index(['is_active', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::table('question_bank_entries', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'is_default']);
            $table->dropColumn('is_default');
        });
    }
};

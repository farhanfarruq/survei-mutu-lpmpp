<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE activity_log ALTER COLUMN causer_id TYPE BIGINT USING causer_id::bigint');

            return;
        }

        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('causer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE activity_log ALTER COLUMN causer_id TYPE VARCHAR(255) USING causer_id::text');

            return;
        }

        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('causer_id')->nullable()->change();
        });
    }
};

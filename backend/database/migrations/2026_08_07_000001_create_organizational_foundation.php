<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizational_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('code', 50)->unique();
            $table->string('name', 160);
            $table->string('type', 40);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'is_active']);
            $table->index(['type', 'is_active']);
        });

        Schema::table('organizational_units', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('organizational_units')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('id');
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        DB::table('users')->orderBy('id')->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'public_id' => (string) Str::uuid7(),
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('public_id')->nullable(false)->change();
        });

        Schema::create('organizational_unit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organizational_unit_id')->constrained()->cascadeOnDelete();
            $table->string('scope_mode', 20)->default('self');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'organizational_unit_id']);
            $table->index(['organizational_unit_id', 'scope_mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizational_unit_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['public_id', 'is_active', 'last_login_at']);
        });

        Schema::dropIfExists('organizational_units');
    }
};

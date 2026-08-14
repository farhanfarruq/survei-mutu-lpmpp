<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

class ApplicationDataResetSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Reset data aplikasi tidak boleh dijalankan di production.');
        }

        $accountTables = [
            'migrations',
            'users',
            'password_reset_tokens',
            'sessions',
            'personal_access_tokens',
            'passkeys',
            'roles',
            'permissions',
            'model_has_roles',
            'model_has_permissions',
            'role_has_permissions',
        ];

        $tables = collect(Schema::getTables())
            ->pluck('name')
            ->reject(fn (string $table): bool => in_array($table, $accountTables, true) || str_starts_with($table, 'sqlite_'))
            ->values();

        if ($tables->isEmpty()) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            $grammar = DB::connection()->getQueryGrammar();
            $tableList = $tables->map(fn (string $table): string => $grammar->wrapTable($table))->implode(', ');
            DB::statement("TRUNCATE TABLE {$tableList} RESTART IDENTITY CASCADE");

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA defer_foreign_keys = ON');
            $tables->each(fn (string $table) => DB::table($table)->delete());

            return;
        }

        Schema::withoutForeignKeyConstraints(
            fn () => $tables->each(fn (string $table) => DB::table($table)->delete()),
        );
    }
}

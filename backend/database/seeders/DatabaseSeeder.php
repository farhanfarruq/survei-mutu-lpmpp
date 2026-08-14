<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seed = function (): void {
            $this->call(ApplicationDataResetSeeder::class);
            $this->call(FoundationSeeder::class);
            $this->call(SurveyManagementSeeder::class);
            $this->call(DashboardSurveySeeder::class);
            $this->call(DashboardAnalyticsSeeder::class);
            $this->call(DashboardFollowUpSeeder::class);
        };

        DB::getDriverName() === 'pgsql' ? DB::transaction($seed) : $seed();
    }
}

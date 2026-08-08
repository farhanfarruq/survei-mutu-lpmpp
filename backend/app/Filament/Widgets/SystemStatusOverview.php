<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class SystemStatusOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Aplikasi', 'Aktif')->description(app()->environment())->color('success'),
            $this->status('Database', fn () => DB::select('select 1')),
            $this->status('Redis', fn () => Redis::connection()->ping()),
            Stat::make('Queue', config('queue.default'))->description('Horizon worker terpisah')->color('info'),
        ];
    }

    private function status(string $label, callable $check): Stat
    {
        try {
            $check();

            return Stat::make($label, 'Terhubung')->color('success');
        } catch (Throwable) {
            return Stat::make($label, 'Tidak tersedia')->color('danger');
        }
    }
}

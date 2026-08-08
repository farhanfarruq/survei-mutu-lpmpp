<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json([
            'data' => ['status' => 'ok', 'service' => 'backend'],
        ]);
    }

    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::select('select 1')),
            'redis' => $this->check(fn () => Redis::connection()->ping()),
            'queue' => config('queue.default') ? 'ok' : 'failed',
        ];
        $ready = ! in_array('failed', $checks, true);

        return response()->json([
            'data' => [
                'status' => $ready ? 'ok' : 'degraded',
                'checks' => $checks,
            ],
        ], $ready ? 200 : 503);
    }

    private function check(callable $check): string
    {
        try {
            $check();

            return 'ok';
        } catch (Throwable) {
            return 'failed';
        }
    }
}

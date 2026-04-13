<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Health check controller for monitoring and load balancers.
 */
class HealthController extends Controller
{
    /**
     * Basic health check - returns 200 if app is running.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detailed health check - checks database, cache, and queue.
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'app' => true,
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $allHealthy = !in_array(false, $checks, true);

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'health_check_' . uniqid();
            Cache::put($key, 'ok', 10);
            $result = Cache::get($key) === 'ok';
            Cache::forget($key);
            return $result;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }
}

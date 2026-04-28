<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheckController extends Controller
{
    /**
     * Check system health status.
     */
    public function index()
    {
        $status = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'server_time' => now()->toIso8601String(),
            'disk_free_space' => disk_free_space(base_path()),
        ];

        $statusCode = in_array(false, array_values($status), true) ? 503 : 200;

        return response()->json([
            'status' => $statusCode === 200 ? 'ok' : 'error',
            'checks' => $status
        ], $statusCode);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Exception $e) {
            // Redis might be optional depending on config
            return false;
        }
    }

    private function checkQueue(): bool
    {
        try {
            $connection = config('queue.default');

            // For database queue, check if the jobs table exists and is accessible
            if ($connection === 'database') {
                DB::table('jobs')->count();
                return true;
            }

            // For Redis queue, check Redis connection
            if ($connection === 'redis') {
                return $this->checkRedis();
            }

            // For sync queue, always return true
            if ($connection === 'sync') {
                return true;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            $testFile = 'health-check-' . time() . '.txt';

            // Try to write a file
            Storage::put($testFile, 'health-check');

            // Verify the file exists
            $exists = Storage::exists($testFile);

            // Clean up
            Storage::delete($testFile);

            return $exists;
        } catch (\Exception $e) {
            return false;
        }
    }
}

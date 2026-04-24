<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ServerMetrics
{
    /**
     * Tables whose row counts we expose as an "app footprint" section. Any
     * missing table is skipped silently so this works on fresh installs.
     *
     * @var array<int, string>
     */
    private const COUNT_TABLES = [
        'users',
        'agent_conversations',
        'agent_conversation_messages',
        'tool_executions',
        'documents',
        'memories',
        'reminders',
        'tasks',
        'notes',
    ];

    /**
     * @return array{
     *     runtime: array<string, string|int|float|null>,
     *     system: array<string, mixed>,
     *     disk: array<string, int|float|null>,
     *     database: array<string, mixed>,
     *     queue: array<string, int|string|null>,
     *     cache: array<string, string|null>,
     * }
     */
    public function snapshot(): array
    {
        return [
            'runtime' => $this->runtime(),
            'system' => $this->system(),
            'disk' => $this->disk(),
            'database' => $this->database(),
            'queue' => $this->queue(),
            'cache' => $this->cache(),
        ];
    }

    /** @return array<string, string|int|float|null> */
    private function runtime(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => (string) app()->environment(),
            'debug' => (bool) config('app.debug'),
            'timezone' => (string) config('app.timezone'),
            'memory_usage_mb' => round(memory_get_usage(true) / 1_048_576, 1),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1_048_576, 1),
            'memory_limit' => (string) ini_get('memory_limit'),
        ];
    }

    /** @return array<string, mixed> */
    private function system(): array
    {
        $load = function_exists('sys_getloadavg') ? @sys_getloadavg() : false;
        $loadAvg = null;
        if (is_array($load) && count($load) === 3) {
            $loadAvg = [
                '1m' => round((float) $load[0], 2),
                '5m' => round((float) $load[1], 2),
                '15m' => round((float) $load[2], 2),
            ];
        }

        return [
            'os' => PHP_OS,
            'hostname' => (string) (gethostname() ?: 'unknown'),
            'load_average' => $loadAvg,
            'uptime_seconds' => $this->uptimeSeconds(),
        ];
    }

    private function uptimeSeconds(): ?int
    {
        $path = '/proc/uptime';
        if (! @is_readable($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $parts = explode(' ', trim($raw));

        return isset($parts[0]) ? (int) round((float) $parts[0]) : null;
    }

    /** @return array<string, int|float|null> */
    private function disk(): array
    {
        $path = storage_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        if ($free === false || $total === false || $total === null) {
            return ['free_gb' => null, 'total_gb' => null, 'used_percent' => null];
        }

        $free = (float) $free;
        $total = (float) $total;

        return [
            'free_gb' => round($free / 1_073_741_824, 2),
            'total_gb' => round($total / 1_073_741_824, 2),
            'used_percent' => $total > 0 ? round((($total - $free) / $total) * 100, 1) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function database(): array
    {
        $driver = (string) config('database.default');
        $connection = DB::connection();

        $counts = [];
        foreach (self::COUNT_TABLES as $table) {
            try {
                if (Schema::hasTable($table)) {
                    $counts[$table] = (int) $connection->table($table)->count();
                }
            } catch (Throwable) {
                // Skip — some connections (read-replicas, shards) may not allow this.
            }
        }

        return [
            'driver' => $driver,
            'name' => (string) config("database.connections.{$driver}.database", ''),
            'row_counts' => $counts,
        ];
    }

    /** @return array<string, int|string|null> */
    private function queue(): array
    {
        $driver = (string) config('queue.default');

        $pending = null;
        $failed = null;

        try {
            if ($driver === 'database' && Schema::hasTable('jobs')) {
                $pending = (int) DB::table('jobs')->count();
            }
            if (Schema::hasTable('failed_jobs')) {
                $failed = (int) DB::table('failed_jobs')->count();
            }
        } catch (Throwable) {
            // Swallow — metrics should never break callers.
        }

        return [
            'driver' => $driver,
            'pending' => $pending,
            'failed' => $failed,
        ];
    }

    /** @return array<string, string|null> */
    private function cache(): array
    {
        return [
            'driver' => (string) config('cache.default'),
            'session_driver' => (string) config('session.driver'),
        ];
    }
}

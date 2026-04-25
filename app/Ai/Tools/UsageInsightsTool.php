<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Livewire\Usage;
use App\Models\User;
use App\Services\ServerMetrics;
use App\Services\UsageAnalytics;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class UsageInsightsTool extends AiTool
{
    private const SECTIONS = [
        'overview',
        'by_function',
        'by_model',
        'top_conversations',
        'tools',
        'server',
        'all',
    ];

    public function __construct(
        private readonly User $user,
        private readonly UsageAnalytics $analytics,
        private readonly ServerMetrics $server,
    ) {}

    public static function toolName(): string
    {
        return 'usage_insights';
    }

    public static function toolLabel(): string
    {
        return 'Usage & Server Insights';
    }

    public static function toolCategory(): string
    {
        return 'analytics';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Query AI token usage, spend estimates, per-model and per-function breakdowns, top conversations, tool-call stats, and (admin only) server health — database row counts, queue/cache drivers, pending/failed jobs, disk, load average. Use when the user asks about their usage, tokens, cost, which model/function consumed the most, how many messages they sent, or server status like "how is the server", "how many pending jobs", "disk space".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'section' => $schema->string()
                ->description('Which slice to return. "overview" for totals + cost, "by_function" per agent, "by_model" per model with cost, "top_conversations" highest-token conversations, "tools" tool-call stats, "server" for admin-only server health, "all" for everything. Defaults to "overview".')
                ->enum(self::SECTIONS)
                ->nullable(),
            'days' => $schema->integer()
                ->description('Lookback window in days. Allowed values: 7, 30, 90, 365. Defaults to 30.')
                ->nullable(),
            'scope' => $schema->string()
                ->description('"me" for this user only, "global" for all users (admin only — silently falls back to "me" for non-admins). Defaults to "me".')
                ->enum(['me', 'global'])
                ->nullable(),
            'context' => $schema->string()
                ->description('Filter by a specific context slug (e.g. "acme", "phoenix", "personal"). If omitted, returns cross-context totals. Unknown slug is ignored.')
                ->nullable(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $section = (string) ($request['section'] ?? 'overview');
        if (! in_array($section, self::SECTIONS, true)) {
            $section = 'overview';
        }

        $days = (int) ($request['days'] ?? 30);
        if (! in_array($days, Usage::RANGES, true)) {
            $days = 30;
        }

        $scope = (string) ($request['scope'] ?? 'me');
        $isAdmin = $this->user->isAdmin();
        if ($scope === 'global' && ! $isAdmin) {
            $scope = 'me';
        }

        if ($section === 'server' && ! $isAdmin) {
            return 'Server health metrics are restricted to admins.';
        }

        $userId = $scope === 'global' ? null : $this->user->id;

        $contextSlug = $request['context'] ?? null;
        $contextLabel = 'all contexts';
        if (is_string($contextSlug) && $contextSlug !== '') {
            $match = $this->user->contexts()->where('slug', $contextSlug)->first();
            $contextLabel = $match !== null ? "{$match->name} ({$match->slug})" : 'all contexts (unknown slug)';
        }

        $lines = [
            "scope: {$scope}",
            "context: {$contextLabel}",
            "window_days: {$days}",
        ];

        if ($section !== 'server') {
            $summary = $this->analytics->summary($userId, $days);
            $lines = array_merge($lines, $this->formatUsage($summary, $section));
        }

        if ($section === 'server' || $section === 'all') {
            if ($isAdmin) {
                $lines[] = '';
                $lines[] = '# Server health';
                $lines = array_merge($lines, $this->formatServer($this->server->snapshot()));
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<int, string>
     */
    private function formatUsage(array $summary, string $section): array
    {
        $out = [];
        $t = $summary['totals'];

        if ($section === 'overview' || $section === 'all') {
            $out[] = '';
            $out[] = '# Overview';
            $out[] = 'total_tokens: '.number_format((int) $t['total_tokens']);
            $out[] = 'prompt_tokens: '.number_format((int) $t['prompt_tokens']);
            $out[] = 'completion_tokens: '.number_format((int) $t['completion_tokens']);
            $out[] = 'reasoning_tokens: '.number_format((int) $t['reasoning_tokens']);
            $out[] = 'cache_read_tokens: '.number_format((int) $t['cache_read_input_tokens']);
            $out[] = 'cache_hit_rate_percent: '.$t['cache_hit_rate'];
            $out[] = 'messages: '.number_format((int) $t['messages']);
            $out[] = 'conversations: '.number_format((int) $t['conversations']);
            $out[] = 'active_users: '.number_format((int) $t['users']);
            $out[] = 'estimated_cost_usd: '.number_format((float) $summary['cost_estimate_usd'], 4);
        }

        if ($section === 'by_function' || $section === 'all') {
            $out[] = '';
            $out[] = '# By function';
            if (empty($summary['by_function'])) {
                $out[] = '(none)';
            } else {
                foreach ($summary['by_function'] as $row) {
                    $out[] = sprintf(
                        '- %s: %s tokens (%s%%) across %s messages',
                        $row['function'],
                        number_format((int) $row['total_tokens']),
                        $row['percent'],
                        number_format((int) $row['messages']),
                    );
                }
            }
        }

        if ($section === 'by_model' || $section === 'all') {
            $out[] = '';
            $out[] = '# By model';
            if (empty($summary['by_model'])) {
                $out[] = '(none)';
            } else {
                foreach ($summary['by_model'] as $row) {
                    $out[] = sprintf(
                        '- %s: %s tokens · $%s · %s messages',
                        $row['model'],
                        number_format((int) $row['total_tokens']),
                        number_format((float) $row['cost_usd'], 4),
                        number_format((int) $row['messages']),
                    );
                }
            }
        }

        if ($section === 'top_conversations' || $section === 'all') {
            $out[] = '';
            $out[] = '# Top conversations';
            if (empty($summary['top_conversations'])) {
                $out[] = '(none)';
            } else {
                foreach ($summary['top_conversations'] as $row) {
                    $out[] = sprintf(
                        '- %s: %s tokens, %s messages',
                        $row['title'] !== '' ? $row['title'] : 'Untitled',
                        number_format((int) $row['total_tokens']),
                        number_format((int) $row['messages']),
                    );
                }
            }
        }

        if ($section === 'tools' || $section === 'all') {
            $out[] = '';
            $out[] = '# Tool calls';
            if (empty($summary['tools'])) {
                $out[] = '(none)';
            } else {
                foreach ($summary['tools'] as $row) {
                    $out[] = sprintf(
                        '- %s: %s calls, avg %s ms',
                        $row['tool'],
                        number_format((int) $row['count']),
                        number_format((int) $row['avg_ms']),
                    );
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $snap
     * @return array<int, string>
     */
    private function formatServer(array $snap): array
    {
        $rt = $snap['runtime'];
        $sys = $snap['system'];
        $disk = $snap['disk'];
        $db = $snap['database'];
        $q = $snap['queue'];
        $cache = $snap['cache'];

        $load = $sys['load_average'];
        $loadStr = $load ? "{$load['1m']} / {$load['5m']} / {$load['15m']}" : 'n/a';

        $out = [
            'php: '.$rt['php_version'],
            'laravel: '.$rt['laravel_version'],
            'environment: '.$rt['environment'].($rt['debug'] ? ' (debug)' : ''),
            'memory_usage_mb: '.$rt['memory_usage_mb'].' (peak '.$rt['peak_memory_mb'].', limit '.$rt['memory_limit'].')',
            'host: '.$sys['hostname'].' ('.$sys['os'].')',
            'load_1_5_15: '.$loadStr,
            'uptime_seconds: '.($sys['uptime_seconds'] ?? 'n/a'),
            'disk_free_gb: '.($disk['free_gb'] ?? 'n/a'),
            'disk_total_gb: '.($disk['total_gb'] ?? 'n/a'),
            'disk_used_percent: '.($disk['used_percent'] ?? 'n/a'),
            'db_driver: '.$db['driver'],
            'queue_driver: '.$q['driver'],
            'queue_pending: '.($q['pending'] ?? 'n/a'),
            'queue_failed: '.($q['failed'] ?? 'n/a'),
            'cache_driver: '.$cache['driver'],
            'session_driver: '.$cache['session_driver'],
        ];

        if (! empty($db['row_counts'])) {
            $out[] = '';
            $out[] = '## Row counts';
            foreach ($db['row_counts'] as $table => $count) {
                $out[] = "- {$table}: ".number_format((int) $count);
            }
        }

        return $out;
    }
}

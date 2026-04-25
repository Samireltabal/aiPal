<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class UsageAnalytics
{
    /**
     * Map raw agent class names to human-friendly function labels.
     * Anything not listed falls back to the short class name.
     */
    private const AGENT_LABELS = [
        'App\\Ai\\Agents\\Chat\\ChatAgent' => 'Chat',
        'App\\Ai\\Agents\\Memory\\MemoryExtractorAgent' => 'Memory Extraction',
        'App\\Ai\\Agents\\Productivity\\ReminderParserAgent' => 'Reminder Parser',
        'App\\Ai\\Agents\\Briefing\\DailyBriefingAgent' => 'Daily Briefing',
    ];

    public function summary(?int $userId, int $days = 30): array
    {
        $since = CarbonImmutable::now()->subDays($days)->startOfDay();

        $totals = $this->totals($userId, $since);
        $tools = $this->toolStats($userId, $since);

        return [
            'days' => $days,
            'since' => $since->toIso8601String(),
            'totals' => $totals,
            'cost_estimate_usd' => $this->totalCostEstimate($userId, $since),
            'daily' => $this->dailyTokens($userId, $since, $days),
            'by_function' => $this->byFunction($userId, $since),
            'by_model' => $this->byModel($userId, $since),
            'top_conversations' => $this->topConversations($userId, $since),
            'tools' => $tools,
        ];
    }

    private function baseMessages(?int $userId, CarbonImmutable $since): Builder
    {
        $q = DB::table('agent_conversation_messages')
            ->where('role', 'assistant')
            ->where('created_at', '>=', $since);

        if ($userId !== null) {
            $q->where('user_id', $userId);
        }

        return $q;
    }

    private function totals(?int $userId, CarbonImmutable $since): array
    {
        $totals = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'cache_read_input_tokens' => 0,
            'cache_write_input_tokens' => 0,
            'reasoning_tokens' => 0,
            'messages' => 0,
            'conversations' => 0,
            'users' => 0,
        ];

        $rows = $this->baseMessages($userId, $since)
            ->select(
                'usage',
                'conversation_id',
                'user_id',
            )
            ->get();

        $conversations = [];
        $users = [];

        foreach ($rows as $row) {
            $u = $this->decodeUsage($row->usage);
            $totals['prompt_tokens'] += $u['prompt_tokens'];
            $totals['completion_tokens'] += $u['completion_tokens'];
            $totals['cache_read_input_tokens'] += $u['cache_read_input_tokens'];
            $totals['cache_write_input_tokens'] += $u['cache_write_input_tokens'];
            $totals['reasoning_tokens'] += $u['reasoning_tokens'];
            $totals['messages']++;
            $conversations[$row->conversation_id] = true;
            if ($row->user_id !== null) {
                $users[$row->user_id] = true;
            }
        }

        $totals['conversations'] = count($conversations);
        $totals['users'] = count($users);
        $totals['total_tokens'] = $totals['prompt_tokens']
            + $totals['completion_tokens']
            + $totals['reasoning_tokens'];
        $totals['cache_hit_rate'] = $this->cacheHitRate(
            $totals['cache_read_input_tokens'],
            $totals['prompt_tokens']
        );

        return $totals;
    }

    private function dailyTokens(?int $userId, CarbonImmutable $since, int $days): array
    {
        $rows = $this->baseMessages($userId, $since)
            ->select('usage', 'created_at')
            ->get();

        $buckets = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $since->addDays($i)->toDateString();
            $buckets[$d] = ['date' => $d, 'prompt' => 0, 'completion' => 0, 'cached' => 0];
        }

        foreach ($rows as $row) {
            $d = CarbonImmutable::parse($row->created_at)->toDateString();
            if (! isset($buckets[$d])) {
                continue;
            }
            $u = $this->decodeUsage($row->usage);
            $buckets[$d]['prompt'] += $u['prompt_tokens'];
            $buckets[$d]['completion'] += $u['completion_tokens'];
            $buckets[$d]['cached'] += $u['cache_read_input_tokens'];
        }

        return array_values($buckets);
    }

    private function byFunction(?int $userId, CarbonImmutable $since): array
    {
        $rows = $this->baseMessages($userId, $since)
            ->select('agent', 'usage')
            ->get();

        $agg = [];
        $grandTokens = 0;

        foreach ($rows as $row) {
            $label = self::AGENT_LABELS[$row->agent] ?? class_basename($row->agent);
            $u = $this->decodeUsage($row->usage);
            $tokens = $u['prompt_tokens'] + $u['completion_tokens'] + $u['reasoning_tokens'];

            $agg[$label] ??= [
                'function' => $label,
                'agent' => $row->agent,
                'messages' => 0,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ];
            $agg[$label]['messages']++;
            $agg[$label]['prompt_tokens'] += $u['prompt_tokens'];
            $agg[$label]['completion_tokens'] += $u['completion_tokens'];
            $agg[$label]['total_tokens'] += $tokens;

            $grandTokens += $tokens;
        }

        foreach ($agg as &$row) {
            $row['percent'] = $grandTokens > 0
                ? round(($row['total_tokens'] / $grandTokens) * 100, 1)
                : 0.0;
        }
        unset($row);

        usort($agg, fn ($a, $b) => $b['total_tokens'] <=> $a['total_tokens']);

        return $agg;
    }

    private function byModel(?int $userId, CarbonImmutable $since): array
    {
        $rows = $this->baseMessages($userId, $since)
            ->select('usage', 'meta')
            ->get();

        $agg = [];

        foreach ($rows as $row) {
            $meta = $this->decodeJson($row->meta);
            $model = is_array($meta)
                ? (string) ($meta['model'] ?? $meta['model_id'] ?? 'unknown')
                : 'unknown';
            $model = $model !== '' ? $model : 'unknown';

            $u = $this->decodeUsage($row->usage);
            $tokens = $u['prompt_tokens'] + $u['completion_tokens'] + $u['reasoning_tokens'];
            $cost = $this->estimateCost($model, $u);

            $agg[$model] ??= [
                'model' => $model,
                'messages' => 0,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'cost_usd' => 0.0,
            ];
            $agg[$model]['messages']++;
            $agg[$model]['prompt_tokens'] += $u['prompt_tokens'];
            $agg[$model]['completion_tokens'] += $u['completion_tokens'];
            $agg[$model]['total_tokens'] += $tokens;
            $agg[$model]['cost_usd'] += $cost;
        }

        foreach ($agg as &$row) {
            $row['cost_usd'] = round($row['cost_usd'], 4);
        }
        unset($row);

        usort($agg, fn ($a, $b) => $b['total_tokens'] <=> $a['total_tokens']);

        return $agg;
    }

    private function topConversations(?int $userId, CarbonImmutable $since, int $limit = 10): array
    {
        $rows = $this->baseMessages($userId, $since)
            ->select('conversation_id', 'usage')
            ->get();

        $agg = [];

        foreach ($rows as $row) {
            $u = $this->decodeUsage($row->usage);
            $tokens = $u['prompt_tokens'] + $u['completion_tokens'] + $u['reasoning_tokens'];

            $agg[$row->conversation_id] ??= [
                'conversation_id' => $row->conversation_id,
                'messages' => 0,
                'total_tokens' => 0,
            ];
            $agg[$row->conversation_id]['messages']++;
            $agg[$row->conversation_id]['total_tokens'] += $tokens;
        }

        usort($agg, fn ($a, $b) => $b['total_tokens'] <=> $a['total_tokens']);
        $agg = array_slice($agg, 0, $limit);

        if ($agg === []) {
            return [];
        }

        $titles = DB::table('agent_conversations')
            ->whereIn('id', array_column($agg, 'conversation_id'))
            ->pluck('title', 'id');

        foreach ($agg as &$row) {
            $row['title'] = (string) ($titles[$row['conversation_id']] ?? 'Untitled');
        }
        unset($row);

        return $agg;
    }

    private function totalCostEstimate(?int $userId, CarbonImmutable $since): float
    {
        $cost = 0.0;
        foreach ($this->byModel($userId, $since) as $row) {
            $cost += (float) $row['cost_usd'];
        }

        return round($cost, 4);
    }

    private function toolStats(?int $userId, CarbonImmutable $since): array
    {
        $q = DB::table('tool_executions')
            ->where('created_at', '>=', $since);

        if ($userId !== null) {
            $q->where('user_id', $userId);
        }

        $rows = $q->select('tool', DB::raw('COUNT(*) as count'), DB::raw('AVG(duration_ms) as avg_ms'))
            ->groupBy('tool')
            ->orderByDesc('count')
            ->get();

        return $rows->map(fn ($r) => [
            'tool' => $r->tool,
            'count' => (int) $r->count,
            'avg_ms' => (int) round((float) $r->avg_ms),
        ])->all();
    }

    private function decodeUsage(?string $json): array
    {
        $defaults = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'cache_read_input_tokens' => 0,
            'cache_write_input_tokens' => 0,
            'reasoning_tokens' => 0,
        ];

        $data = $this->decodeJson($json);
        if (! is_array($data)) {
            return $defaults;
        }

        foreach ($defaults as $key => $_) {
            $defaults[$key] = (int) ($data[$key] ?? 0);
        }

        return $defaults;
    }

    private function decodeJson(?string $json): mixed
    {
        if ($json === null || $json === '') {
            return null;
        }

        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    private function cacheHitRate(int $cacheRead, int $prompt): float
    {
        $denom = $cacheRead + $prompt;

        return $denom > 0 ? round(($cacheRead / $denom) * 100, 1) : 0.0;
    }

    public function estimateCost(string $model, array $usage): float
    {
        $unit = (int) config('ai-pricing.unit', 1_000_000);
        $pricing = $this->pricingFor($model);

        if ($pricing === null) {
            return 0.0;
        }

        $cachedPrice = $pricing['cached_input'] ?? $pricing['input'];
        $billablePrompt = max(
            0,
            ($usage['prompt_tokens'] ?? 0) - ($usage['cache_read_input_tokens'] ?? 0)
        );

        $cost = ($billablePrompt / $unit) * $pricing['input']
            + (($usage['cache_read_input_tokens'] ?? 0) / $unit) * $cachedPrice
            + (($usage['completion_tokens'] ?? 0) / $unit) * $pricing['output']
            + (($usage['reasoning_tokens'] ?? 0) / $unit) * $pricing['output'];

        return $cost;
    }

    private function pricingFor(string $model): ?array
    {
        $model = strtolower($model);
        $models = (array) config('ai-pricing.models', []);

        $best = null;
        $bestLen = -1;
        foreach ($models as $key => $pricing) {
            $k = strtolower((string) $key);
            if ($k !== '' && str_starts_with($model, $k) && strlen($k) > $bestLen) {
                $best = $pricing;
                $bestLen = strlen($k);
            }
        }

        return $best;
    }
}

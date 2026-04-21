<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\ToolExecution;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

abstract class AiTool implements Tool
{
    /** Machine name used for storage and rate limiting. */
    abstract public static function toolName(): string;

    /** Human-readable label shown in the settings UI. */
    abstract public static function toolLabel(): string;

    /** Category for grouping in the settings UI. */
    abstract public static function toolCategory(): string;

    /** The user ID to associate with logged executions. Return null to skip logging. */
    abstract protected function userId(): ?int;

    final public function handle(Request $request): Stringable|string
    {
        $start = hrtime(true);

        try {
            $result = $this->execute($request);
        } catch (\Throwable $e) {
            Log::warning('Tool execution failed', [
                'tool' => static::toolName(),
                'error' => $e->getMessage(),
            ]);

            return 'Tool "'.static::toolName().'" is temporarily unavailable: '.$e->getMessage();
        }

        $durationMs = (int) ((hrtime(true) - $start) / 1_000_000);

        $this->logExecution($durationMs);

        return $result;
    }

    abstract protected function execute(Request $request): Stringable|string;

    private function logExecution(int $durationMs): void
    {
        $userId = $this->userId();

        if ($userId === null) {
            return;
        }

        ToolExecution::create([
            'user_id' => $userId,
            'tool' => static::toolName(),
            'duration_ms' => min($durationMs, 65535),
        ]);
    }
}

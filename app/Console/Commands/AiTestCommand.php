<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

use function Laravel\Ai\agent;

class AiTestCommand extends Command
{
    protected $signature = 'ai:test {provider? : Provider name (anthropic, openai, deepseek, xai, gemini, ollama)}
                            {--all : Test all configured providers}';

    protected $description = 'Test connectivity to AI provider(s)';

    public function handle(): int
    {
        $providers = $this->option('all')
            ? array_keys(config('ai.providers', []))
            : [$this->argument('provider') ?? config('ai.default', 'anthropic')];

        $hasFailure = false;

        foreach ($providers as $provider) {
            $this->testProvider($provider, $hasFailure);
        }

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }

    private function testProvider(string $provider, bool &$hasFailure): void
    {
        if (! array_key_exists($provider, config('ai.providers', []))) {
            $this->error("  Unknown provider: {$provider}");
            $hasFailure = true;

            return;
        }

        $key = config("ai.providers.{$provider}.key", '');
        if (in_array($provider, ['anthropic', 'openai', 'deepseek', 'xai', 'gemini']) && empty($key)) {
            $this->warn("  [{$provider}] SKIP — API key not configured");

            return;
        }

        $model = config("ai.models.{$provider}");
        $this->info("  Testing {$provider}".($model ? " ({$model})" : '').' ...');

        try {
            $response = agent(instructions: 'You are a test assistant. Respond briefly.')
                ->prompt('Say "ok" and nothing else.', provider: $provider, model: $model);

            $text = trim((string) $response);
            $this->line("  <fg=green>✓ {$provider}:</> {$text}");
        } catch (\Throwable $e) {
            $this->line("  <fg=red>✗ {$provider}:</> {$e->getMessage()}");
            $hasFailure = true;
        }
    }
}

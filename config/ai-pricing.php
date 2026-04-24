<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Token Pricing (USD per 1M tokens)
|--------------------------------------------------------------------------
|
| Used by App\Services\UsageAnalytics to estimate spend. These are public
| list prices as of 2026-Q1 and are best-effort estimates only — actual
| billing depends on your contract, region, and provider promotions.
|
| Keys are matched case-insensitively against the model identifier; the
| longest matching prefix wins (e.g. "claude-sonnet-4-6" matches
| "claude-sonnet" before "claude-").
|
| Each entry:
|   input         — prompt tokens
|   output        — completion tokens
|   cached_input  — cache read input tokens (defaults to input if null)
|
*/

return [

    'currency' => 'USD',

    'unit' => 1_000_000,

    'models' => [
        // ---- Anthropic ----
        'claude-opus-4' => ['input' => 15.00, 'output' => 75.00, 'cached_input' => 1.50],
        'claude-sonnet-4' => ['input' => 3.00, 'output' => 15.00, 'cached_input' => 0.30],
        'claude-haiku-4' => ['input' => 1.00, 'output' => 5.00, 'cached_input' => 0.10],
        'claude-3-5-sonnet' => ['input' => 3.00, 'output' => 15.00, 'cached_input' => 0.30],
        'claude-3-5-haiku' => ['input' => 0.80, 'output' => 4.00, 'cached_input' => 0.08],
        'claude-3-opus' => ['input' => 15.00, 'output' => 75.00, 'cached_input' => 1.50],
        'claude-3-haiku' => ['input' => 0.25, 'output' => 1.25, 'cached_input' => 0.03],

        // ---- OpenAI chat ----
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60, 'cached_input' => 0.075],
        'gpt-4o' => ['input' => 2.50, 'output' => 10.00, 'cached_input' => 1.25],
        'gpt-4.1-mini' => ['input' => 0.40, 'output' => 1.60, 'cached_input' => 0.10],
        'gpt-4.1-nano' => ['input' => 0.10, 'output' => 0.40, 'cached_input' => 0.025],
        'gpt-4.1' => ['input' => 2.00, 'output' => 8.00, 'cached_input' => 0.50],
        'gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00, 'cached_input' => 10.00],
        'gpt-3.5-turbo' => ['input' => 0.50, 'output' => 1.50, 'cached_input' => 0.50],
        'o1-mini' => ['input' => 3.00, 'output' => 12.00, 'cached_input' => 1.50],
        'o1' => ['input' => 15.00, 'output' => 60.00, 'cached_input' => 7.50],
        'o3-mini' => ['input' => 1.10, 'output' => 4.40, 'cached_input' => 0.55],

        // ---- OpenAI embeddings / audio (per-token where applicable) ----
        'text-embedding-3-small' => ['input' => 0.02, 'output' => 0.00, 'cached_input' => 0.02],
        'text-embedding-3-large' => ['input' => 0.13, 'output' => 0.00, 'cached_input' => 0.13],
        'text-embedding-ada-002' => ['input' => 0.10, 'output' => 0.00, 'cached_input' => 0.10],

        // ---- Google Gemini ----
        'gemini-2.0-flash' => ['input' => 0.10, 'output' => 0.40, 'cached_input' => 0.025],
        'gemini-2.5-pro' => ['input' => 1.25, 'output' => 10.00, 'cached_input' => 0.31],
        'gemini-2.5-flash' => ['input' => 0.30, 'output' => 2.50, 'cached_input' => 0.075],
        'gemini-1.5-pro' => ['input' => 1.25, 'output' => 5.00, 'cached_input' => 0.31],
        'gemini-1.5-flash' => ['input' => 0.075, 'output' => 0.30, 'cached_input' => 0.019],

        // ---- DeepSeek ----
        'deepseek-chat' => ['input' => 0.27, 'output' => 1.10, 'cached_input' => 0.07],
        'deepseek-reasoner' => ['input' => 0.55, 'output' => 2.19, 'cached_input' => 0.14],

        // ---- xAI (Grok) ----
        'grok-4' => ['input' => 3.00, 'output' => 15.00, 'cached_input' => 0.75],
        'grok-3' => ['input' => 3.00, 'output' => 15.00, 'cached_input' => 0.75],
        'grok-3-mini' => ['input' => 0.30, 'output' => 0.50, 'cached_input' => 0.075],
        'grok-2' => ['input' => 2.00, 'output' => 10.00, 'cached_input' => 0.50],
        'grok-beta' => ['input' => 5.00, 'output' => 15.00, 'cached_input' => 5.00],

        // ---- Local / self-hosted ----
        'llama' => ['input' => 0.00, 'output' => 0.00, 'cached_input' => 0.00],
        'ollama' => ['input' => 0.00, 'output' => 0.00, 'cached_input' => 0.00],
    ],

];

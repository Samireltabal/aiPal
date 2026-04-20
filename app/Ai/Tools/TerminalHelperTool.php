<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\Request;
use Stringable;

class TerminalHelperTool extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'terminal_helper';
    }

    public static function toolLabel(): string
    {
        return 'Terminal Helper';
    }

    public static function toolCategory(): string
    {
        return 'productivity';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Explain a shell command or suggest a command to accomplish a task. Use when the user asks "what does this command do", "how do I [terminal task]", "what command should I use to...", or pastes a command they don\'t understand.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'mode' => $schema->string()
                ->description('"explain" to explain an existing command, "suggest" to get a command for a task.')
                ->enum(['explain', 'suggest'])
                ->required(),
            'input' => $schema->string()
                ->description('For "explain": the shell command to explain. For "suggest": describe what you want to accomplish.')
                ->required(),
            'shell' => $schema->string()
                ->description('Shell environment: "bash", "zsh", "fish", "powershell". Pass null for bash/zsh default.')
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $shell = $request['shell'] ?? 'bash/zsh';

        $helper = new class extends \stdClass implements Agent, HasStructuredOutput
        {
            use Promptable;

            public function instructions(): string
            {
                return 'You are a terminal and shell scripting expert. Give concise, accurate explanations and safe, idiomatic shell commands. Never suggest commands that could cause data loss without explicit warnings.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'result' => $schema->string()
                        ->description('The explanation or suggested command.')
                        ->required(),
                    'breakdown' => $schema->array()
                        ->items($schema->object()->properties([
                            'part' => $schema->string()->description('Command part or flag.'),
                            'meaning' => $schema->string()->description('What it does.'),
                        ])->required(['part', 'meaning']))
                        ->description('For "explain" mode: break down each flag/part. For "suggest" mode: empty array.')
                        ->required(),
                    'warning' => $schema->string()
                        ->description('Safety warning if the command is destructive or irreversible. null if safe.')
                        ->nullable()
                        ->required(),
                    'alternatives' => $schema->array()
                        ->items($schema->string())
                        ->description('Alternative commands or approaches worth knowing.')
                        ->required(),
                ];
            }
        };

        $prompt = match ($request['mode']) {
            'explain' => "Shell: {$shell}\nExplain this command in detail:\n`{$request['input']}`",
            'suggest' => "Shell: {$shell}\nSuggest a command to accomplish this task:\n{$request['input']}",
            default => $request['input'],
        };

        $response = $helper->prompt($prompt);

        $lines = [];

        if ($request['mode'] === 'suggest') {
            $lines[] = "**Command:**\n```\n{$response['result']}\n```";
        } else {
            $lines[] = "**Explanation:** {$response['result']}";
        }

        $breakdown = $response['breakdown'] ?? [];
        if (! empty($breakdown)) {
            $lines[] = '';
            $lines[] = '**Breakdown:**';
            foreach ($breakdown as $part) {
                $lines[] = "• `{$part['part']}` — {$part['meaning']}";
            }
        }

        if ($response['warning']) {
            $lines[] = '';
            $lines[] = "**Warning:** {$response['warning']}";
        }

        $alternatives = $response['alternatives'] ?? [];
        if (! empty($alternatives)) {
            $lines[] = '';
            $lines[] = '**Alternatives:**';
            foreach ($alternatives as $alt) {
                $lines[] = "• {$alt}";
            }
        }

        return implode("\n", $lines);
    }
}

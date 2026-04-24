<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\UsageAnalytics;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Usage extends Component
{
    public const RANGES = [7, 30, 90, 365];

    #[Url(as: 'range')]
    public int $days = 30;

    #[Url(as: 'scope')]
    public string $scope = 'me';

    public function setRange(int $days): void
    {
        $this->days = in_array($days, self::RANGES, true) ? $days : 30;
    }

    public function setScope(string $scope): void
    {
        if (! Auth::user()?->isAdmin()) {
            $this->scope = 'me';

            return;
        }

        $this->scope = $scope === 'global' ? 'global' : 'me';
    }

    public function render(UsageAnalytics $analytics): View
    {
        $user = Auth::user();
        $isAdmin = (bool) $user?->isAdmin();

        $effectiveScope = $isAdmin ? $this->scope : 'me';
        $userId = $effectiveScope === 'global' ? null : $user->id;

        $summary = $analytics->summary($userId, $this->days);

        return view('livewire.usage', [
            'summary' => $summary,
            'aiConfig' => $this->buildAiConfig(),
            'isAdmin' => $isAdmin,
            'effectiveScope' => $effectiveScope,
            'ranges' => self::RANGES,
        ]);
    }

    /**
     * Active provider/model per AI function — mirrors the table previously
     * shown in Settings. Kept here so Settings stays focused on user prefs.
     */
    private function buildAiConfig(): array
    {
        $defaultProvider = (string) config('ai.default');
        $defaultModel = (string) config("ai.models.{$defaultProvider}", '—');

        $agentProvider = fn (string $key): string => (string) (config("ai.agents.{$key}.provider") ?: $defaultProvider);
        $agentModel = fn (string $key): string => (string) (config("ai.agents.{$key}.model") ?: config("ai.models.{$agentProvider($key)}", '—'));

        $embProvider = (string) config('ai.default_for_embeddings', 'openai');
        $sttProvider = (string) config('ai.default_for_transcription', 'openai');
        $ttsProvider = (string) config('ai.default_for_audio', 'openai');

        $allChat = ['anthropic', 'openai', 'deepseek', 'xai', 'gemini', 'ollama'];
        $structured = ['anthropic', 'openai', 'gemini', 'xai'];

        return [
            [
                'name' => 'Chat',
                'description' => 'Main conversation with your assistant',
                'provider' => $defaultProvider,
                'model' => $defaultModel,
                'env_vars' => ['AI_DEFAULT_PROVIDER', '{PROVIDER}_DEFAULT_MODEL'],
                'compatible' => $allChat,
            ],
            [
                'name' => 'Memory Extraction',
                'description' => 'Extracts facts from conversations in the background',
                'provider' => $agentProvider('memory_extractor'),
                'model' => $agentModel('memory_extractor'),
                'env_vars' => ['MEMORY_EXTRACTOR_PROVIDER', 'MEMORY_EXTRACTOR_MODEL'],
                'compatible' => $structured,
                'note' => 'Requires structured output support',
            ],
            [
                'name' => 'Reminder Parser',
                'description' => 'Parses natural language reminders in chat',
                'provider' => $agentProvider('reminder_parser'),
                'model' => $agentModel('reminder_parser'),
                'env_vars' => ['REMINDER_PARSER_PROVIDER', 'REMINDER_PARSER_MODEL'],
                'compatible' => $structured,
                'note' => 'Requires structured output support',
            ],
            [
                'name' => 'Daily Briefing',
                'description' => 'Generates your scheduled morning briefing email',
                'provider' => $agentProvider('daily_briefing'),
                'model' => $agentModel('daily_briefing'),
                'env_vars' => ['DAILY_BRIEFING_PROVIDER', 'DAILY_BRIEFING_MODEL'],
                'compatible' => $allChat,
            ],
            [
                'name' => 'Embeddings',
                'description' => 'Powers semantic search for memories, documents & notes',
                'provider' => $embProvider,
                'model' => (string) (config('ai.embedding_model') ?: config("ai.models.{$embProvider}", '—')),
                'env_vars' => ['AI_DEFAULT_EMBEDDINGS_PROVIDER', 'AI_EMBEDDING_MODEL', 'AI_EMBEDDING_DIMENSIONS'],
                'compatible' => ['openai', 'ollama', 'gemini'],
                'note' => 'Changing dimensions requires a DB migration & re-ingestion',
            ],
            [
                'name' => 'Voice Transcription (STT)',
                'description' => 'Converts your voice input to text',
                'provider' => $sttProvider,
                'model' => (string) (config('ai.stt_model') ?: '—'),
                'env_vars' => ['AI_DEFAULT_STT_PROVIDER', 'AI_STT_MODEL'],
                'compatible' => ['openai', 'gemini'],
            ],
            [
                'name' => 'Text-to-Speech (TTS)',
                'description' => 'Reads assistant responses aloud',
                'provider' => $ttsProvider,
                'model' => (string) (config('ai.tts_model') ?: '—'),
                'env_vars' => ['AI_DEFAULT_AUDIO_PROVIDER', 'AI_TTS_MODEL'],
                'compatible' => ['openai', 'eleven'],
            ],
        ];
    }
}

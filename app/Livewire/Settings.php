<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Ai\Services\ToolRegistry;
use App\Jobs\GenerateAvatarJob;
use App\Models\UserToolSetting;
use App\Services\PersonaGenerator;
use App\Services\WebPushService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Settings extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:60')]
    public string $assistantName = '';

    #[Validate('required|in:friendly,professional,enthusiastic,calm,direct')]
    public string $tone = '';

    #[Validate('required|in:casual,semi-formal,formal')]
    public string $formality = '';

    #[Validate('required|in:none,light,moderate,frequent')]
    public string $humorLevel = '';

    #[Validate('nullable|string|max:500')]
    public string $backstory = '';

    #[Validate('required|in:alloy,ash,coral,echo,fable,onyx,nova,shimmer')]
    public string $ttsVoice = 'alloy';

    public string $systemPrompt = '';

    public bool $regenerating = false;

    public bool $saved = false;

    #[Validate('nullable|file|mimes:json|max:512')]
    public mixed $importFile = null;

    public string $importError = '';

    public bool $importSuccess = false;

    public bool $generatingAvatar = false;

    public bool $avatarQueued = false;

    public bool $toolSaved = false;

    public bool $briefingEnabled = false;

    #[Validate('required|date_format:H:i,H:i:s')]
    public string $briefingTime = '08:00';

    #[Validate('required|timezone')]
    public string $briefingTimezone = 'UTC';

    public bool $briefingSaved = false;

    public bool $pushTestSent = false;

    #[Validate('required|in:email,telegram,whatsapp,webhook')]
    public string $defaultReminderChannel = 'email';

    #[Validate('nullable|string|regex:/^\d+$/')]
    public ?string $telegramChatId = null;

    public bool $telegramSaved = false;

    #[Validate('nullable|string|regex:/^\d+$/|max:15')]
    public ?string $whatsappPhone = null;

    public bool $whatsappSaved = false;

    #[Validate('nullable|url|max:255')]
    public ?string $jiraHost = null;

    #[Validate('nullable|email|max:255')]
    public ?string $jiraEmail = null;

    #[Validate('nullable|string|max:255')]
    public ?string $jiraToken = null;

    public bool $jiraSaved = false;

    #[Validate('nullable|url|max:255')]
    public ?string $gitlabHost = null;

    #[Validate('nullable|string|max:255')]
    public ?string $gitlabToken = null;

    public bool $gitlabSaved = false;

    #[Validate('nullable|string|max:255')]
    public ?string $githubToken = null;

    public bool $githubSaved = false;

    public function mount(): void
    {
        $user = Auth::user();
        $persona = $user->persona;

        if ($persona) {
            $this->assistantName = $persona->assistant_name;
            $this->tone = $persona->tone;
            $this->formality = $persona->formality;
            $this->humorLevel = $persona->humor_level;
            $this->backstory = $persona->backstory ?? '';
            $this->ttsVoice = $persona->tts_voice ?? 'alloy';
            $this->systemPrompt = $persona->system_prompt;
        }

        $this->briefingEnabled = (bool) $user->briefing_enabled;
        $this->briefingTime = substr($user->briefing_time ?? '08:00', 0, 5);
        $this->briefingTimezone = $user->briefing_timezone ?? 'UTC';
        $this->defaultReminderChannel = $user->default_reminder_channel ?? 'email';

        $this->telegramChatId = $user->telegram_chat_id;
        $this->whatsappPhone = $user->whatsapp_phone;
        $this->jiraHost = $user->jira_host;
        $this->jiraEmail = $user->jira_email;
        $this->jiraToken = $user->jira_token;
        $this->gitlabHost = $user->gitlab_host ?? 'https://gitlab.com';
        $this->gitlabToken = $user->gitlab_token;
        $this->githubToken = $user->github_token;
    }

    public function regenerate(): void
    {
        $this->validate();
        $this->regenerating = true;

        $generator = new PersonaGenerator;

        $this->systemPrompt = $generator->generate([
            'assistant_name' => $this->assistantName,
            'tone' => $this->tone,
            'formality' => $this->formality,
            'humor_level' => $this->humorLevel,
            'backstory' => $this->backstory ?: 'A general-purpose personal assistant',
        ]);

        $this->save(regenerated: true);

        $this->regenerating = false;
    }

    public function save(bool $regenerated = false): void
    {
        if (! $regenerated) {
            $this->validate();
        }

        $persona = Auth::user()->persona;

        $persona->update([
            'assistant_name' => $this->assistantName,
            'tone' => $this->tone,
            'formality' => $this->formality,
            'humor_level' => $this->humorLevel,
            'backstory' => $this->backstory,
            'tts_voice' => $this->ttsVoice,
            'system_prompt' => $this->systemPrompt,
        ]);

        $this->saved = true;
        $this->dispatch('saved');
    }

    public function toggleTool(string $toolName): void
    {
        $userId = Auth::id();

        $setting = UserToolSetting::query()
            ->where('user_id', $userId)
            ->where('tool', $toolName)
            ->first();

        if ($setting) {
            $setting->update(['enabled' => ! $setting->enabled]);
        } else {
            UserToolSetting::create([
                'user_id' => $userId,
                'tool' => $toolName,
                'enabled' => false,
            ]);
        }

        $this->toolSaved = true;
    }

    public function saveTelegramSettings(): void
    {
        $this->validateOnly('telegramChatId');

        Auth::user()->update(['telegram_chat_id' => $this->telegramChatId ?: null]);

        $this->telegramSaved = true;
    }

    public function saveWhatsAppSettings(): void
    {
        $this->validateOnly('whatsappPhone');

        Auth::user()->update(['whatsapp_phone' => $this->whatsappPhone ?: null]);

        $this->whatsappSaved = true;
    }

    public function saveGitLabSettings(): void
    {
        $this->validateOnly('gitlabHost');
        $this->validateOnly('gitlabToken');

        Auth::user()->update([
            'gitlab_host' => $this->gitlabHost ?: 'https://gitlab.com',
            'gitlab_token' => $this->gitlabToken ?: null,
        ]);

        $this->gitlabSaved = true;
    }

    public function saveJiraSettings(): void
    {
        $this->validateOnly('jiraHost');
        $this->validateOnly('jiraEmail');
        $this->validateOnly('jiraToken');

        Auth::user()->update([
            'jira_host' => $this->jiraHost ?: null,
            'jira_email' => $this->jiraEmail ?: null,
            'jira_token' => $this->jiraToken ?: null,
        ]);

        $this->jiraSaved = true;
    }

    public function sendTestPush(): void
    {
        $user = Auth::user();

        if (! $user->push_notifications_enabled) {
            return;
        }

        app(WebPushService::class)->send($user, '🔔 aiPal', 'Push notifications are working!', '/');

        $this->pushTestSent = true;
    }

    public function saveGitHubSettings(): void
    {
        $this->validateOnly('githubToken');

        Auth::user()->update([
            'github_token' => $this->githubToken ?: null,
        ]);

        $this->githubSaved = true;
    }

    public function saveBriefingSettings(): void
    {
        $this->validateOnly('briefingTime');
        $this->validateOnly('briefingTimezone');
        $this->validateOnly('defaultReminderChannel');

        Auth::user()->update([
            'briefing_enabled' => $this->briefingEnabled,
            'briefing_time' => $this->briefingTime,
            'briefing_timezone' => $this->briefingTimezone,
            'default_reminder_channel' => $this->defaultReminderChannel,
        ]);

        $this->briefingSaved = true;
    }

    public function import(): void
    {
        $this->importError = '';
        $this->importSuccess = false;

        $this->validateOnly('importFile');

        if (! $this->importFile) {
            $this->importError = 'Please select a JSON file to import.';

            return;
        }

        $contents = file_get_contents($this->importFile->getRealPath());
        $data = json_decode($contents, true);

        if (! is_array($data)) {
            $this->importError = 'Invalid JSON file.';

            return;
        }

        $allowed_tones = ['friendly', 'professional', 'enthusiastic', 'calm', 'direct'];
        $allowed_formalities = ['casual', 'semi-formal', 'formal'];
        $allowed_humor = ['none', 'light', 'moderate', 'frequent'];

        $name = trim($data['assistant_name'] ?? '');
        $tone = $data['tone'] ?? '';
        $formality = $data['formality'] ?? '';
        $humor = $data['humor_level'] ?? '';

        if (! $name || strlen($name) > 60) {
            $this->importError = 'Invalid or missing assistant_name in JSON.';

            return;
        }

        if (! in_array($tone, $allowed_tones, true)) {
            $this->importError = 'Invalid tone value in JSON.';

            return;
        }

        if (! in_array($formality, $allowed_formalities, true)) {
            $this->importError = 'Invalid formality value in JSON.';

            return;
        }

        if (! in_array($humor, $allowed_humor, true)) {
            $this->importError = 'Invalid humor_level value in JSON.';

            return;
        }

        $this->assistantName = $name;
        $this->tone = $tone;
        $this->formality = $formality;
        $this->humorLevel = $humor;
        $this->backstory = substr(trim($data['backstory'] ?? ''), 0, 500);
        $this->systemPrompt = trim($data['system_prompt'] ?? $this->systemPrompt);

        $this->importFile = null;
        $this->importSuccess = true;
    }

    public function generateAvatar(): void
    {
        $persona = Auth::user()->persona;

        if (! $persona) {
            return;
        }

        GenerateAvatarJob::dispatch($persona->id);
        $this->avatarQueued = true;
    }

    public function render(): View
    {
        $user = Auth::user();
        $tools = app(ToolRegistry::class)->allWithSettings($user);

        return view('livewire.settings', [
            'avatarUrl' => $this->resolveAvatarUrl(),
            'tools' => $tools->groupBy('category'),
            'googleConnected' => $user->hasGoogleConnected(),
            'telegramLinked' => $user->hasTelegramLinked(),
            'aiConfig' => $this->buildAiConfig(),
        ]);
    }

    private function buildAiConfig(): array
    {
        $defaultProvider = (string) config('ai.default');
        $defaultModel = (string) config("ai.models.{$defaultProvider}", '—');

        $agentProvider = fn (string $key): string => (string) (config("ai.agents.{$key}.provider") ?: $defaultProvider);
        $agentModel = fn (string $key): string => (string) (config("ai.agents.{$key}.model") ?: config("ai.models.{$agentProvider($key)}", '—'));

        $embProvider = (string) config('ai.default_for_embeddings', 'openai');
        $sttProvider = (string) config('ai.default_for_transcription', 'openai');
        $ttsProvider = (string) config('ai.default_for_audio', 'openai');

        return [
            [
                'name' => 'Chat',
                'description' => 'Main conversation with your assistant',
                'provider' => $defaultProvider,
                'model' => $defaultModel,
                'env_vars' => ['AI_DEFAULT_PROVIDER', '{PROVIDER}_DEFAULT_MODEL'],
                'compatible' => ['anthropic', 'openai', 'deepseek', 'xai', 'gemini', 'ollama'],
            ],
            [
                'name' => 'Memory Extraction',
                'description' => 'Extracts facts from conversations in the background',
                'provider' => $agentProvider('memory_extractor'),
                'model' => $agentModel('memory_extractor'),
                'env_vars' => ['MEMORY_EXTRACTOR_PROVIDER', 'MEMORY_EXTRACTOR_MODEL'],
                'compatible' => ['anthropic', 'openai', 'gemini'],
                'note' => 'Requires structured output support',
            ],
            [
                'name' => 'Reminder Parser',
                'description' => 'Parses natural language reminders in chat',
                'provider' => $agentProvider('reminder_parser'),
                'model' => $agentModel('reminder_parser'),
                'env_vars' => ['REMINDER_PARSER_PROVIDER', 'REMINDER_PARSER_MODEL'],
                'compatible' => ['anthropic', 'openai', 'gemini'],
                'note' => 'Requires structured output support',
            ],
            [
                'name' => 'Daily Briefing',
                'description' => 'Generates your scheduled morning briefing email',
                'provider' => $agentProvider('daily_briefing'),
                'model' => $agentModel('daily_briefing'),
                'env_vars' => ['DAILY_BRIEFING_PROVIDER', 'DAILY_BRIEFING_MODEL'],
                'compatible' => ['anthropic', 'openai', 'deepseek', 'xai', 'gemini', 'ollama'],
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

    private function resolveAvatarUrl(): ?string
    {
        $path = Auth::user()->persona?->avatar_path;

        return $path ? asset('storage/'.$path) : null;
    }
}

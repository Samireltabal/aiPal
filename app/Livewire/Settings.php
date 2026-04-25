<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Ai\Services\ToolRegistry;
use App\Jobs\GenerateAvatarJob;
use App\Models\Connection;
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

    public bool $inboundEmailSaved = false;

    public bool $inboundEmailCopied = false;

    public bool $pushTestSent = false;

    #[Validate('required|in:email,telegram,whatsapp,webhook')]
    public string $defaultReminderChannel = 'email';

    #[Validate('nullable|string|regex:/^\d+$/')]
    public ?string $telegramChatId = null;

    public bool $telegramSaved = false;

    #[Validate('nullable|string|regex:/^\d+$/|max:15')]
    public ?string $whatsappPhone = null;

    public bool $whatsappSaved = false;

    // — Jira "add account" form —
    #[Validate('nullable|string|max:60')]
    public ?string $jiraLabel = null;

    #[Validate('nullable|url|max:255')]
    public ?string $jiraHost = null;

    #[Validate('nullable|email|max:255')]
    public ?string $jiraEmail = null;

    #[Validate('nullable|string|max:255')]
    public ?string $jiraToken = null;

    public bool $jiraSaved = false;

    // — GitLab "add account" form —
    #[Validate('nullable|string|max:60')]
    public ?string $gitlabLabel = null;

    #[Validate('nullable|url|max:255')]
    public ?string $gitlabHost = null;

    #[Validate('nullable|string|max:255')]
    public ?string $gitlabToken = null;

    public bool $gitlabSaved = false;

    // — GitHub "add account" form —
    #[Validate('nullable|string|max:60')]
    public ?string $githubLabel = null;

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
        $this->gitlabHost = 'https://gitlab.com';
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

    public function addGitLabAccount(): void
    {
        $this->validateOnly('gitlabHost');
        $this->validateOnly('gitlabToken');

        $token = trim((string) $this->gitlabToken);
        if ($token === '') {
            return;
        }

        $user = Auth::user();
        $host = $this->gitlabHost ?: 'https://gitlab.com';
        $label = $this->gitlabLabel ?: parse_url($host, PHP_URL_HOST) ?: 'GitLab';

        $user->connections()->create([
            'context_id' => $user->defaultContext()?->id,
            'provider' => Connection::PROVIDER_GITLAB,
            'capabilities' => [Connection::CAPABILITY_CODE],
            'label' => $label,
            'identifier' => $host,
            'credentials' => ['host' => $host, 'token' => $token],
            'enabled' => true,
            'is_default' => ! $user->hasGitLabConnected(),
        ]);

        $this->gitlabLabel = null;
        $this->gitlabHost = 'https://gitlab.com';
        $this->gitlabToken = null;
        $this->gitlabSaved = true;
    }

    public function addJiraAccount(): void
    {
        $this->validateOnly('jiraHost');
        $this->validateOnly('jiraEmail');
        $this->validateOnly('jiraToken');

        $host = trim((string) $this->jiraHost);
        $email = trim((string) $this->jiraEmail);
        $token = trim((string) $this->jiraToken);

        if ($host === '' || $email === '' || $token === '') {
            return;
        }

        $user = Auth::user();
        $label = $this->jiraLabel ?: parse_url($host, PHP_URL_HOST) ?: 'Jira';

        $user->connections()->create([
            'context_id' => $user->defaultContext()?->id,
            'provider' => Connection::PROVIDER_JIRA,
            'capabilities' => [Connection::CAPABILITY_ISSUES],
            'label' => $label,
            'identifier' => $email.'@'.$host,
            'credentials' => ['host' => $host, 'email' => $email, 'token' => $token],
            'enabled' => true,
            'is_default' => ! $user->hasJiraConnected(),
        ]);

        $this->jiraLabel = null;
        $this->jiraHost = null;
        $this->jiraEmail = null;
        $this->jiraToken = null;
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

    public function addGitHubAccount(): void
    {
        $this->validateOnly('githubToken');

        $token = trim((string) $this->githubToken);
        if ($token === '') {
            return;
        }

        $user = Auth::user();
        $label = $this->githubLabel ?: 'GitHub';

        $user->connections()->create([
            'context_id' => $user->defaultContext()?->id,
            'provider' => Connection::PROVIDER_GITHUB,
            'capabilities' => [Connection::CAPABILITY_CODE],
            'label' => $label,
            'identifier' => 'github.com',
            'credentials' => ['token' => $token],
            'enabled' => true,
            'is_default' => ! $user->hasGitHubConnected(),
        ]);

        $this->githubLabel = null;
        $this->githubToken = null;
        $this->githubSaved = true;
    }

    public function removeIntegrationConnection(int $connectionId): void
    {
        $user = Auth::user();

        $connection = $user->connections()->whereIn('provider', [
            Connection::PROVIDER_GITHUB,
            Connection::PROVIDER_GITLAB,
            Connection::PROVIDER_JIRA,
            Connection::PROVIDER_GOOGLE,
        ])->find($connectionId);

        if ($connection === null) {
            return;
        }

        $wasDefault = $connection->is_default;
        $provider = $connection->provider;
        $connection->delete();

        // If we removed the default, promote another connection of the same
        // provider to default so tools keep working without the user having
        // to flip a flag manually.
        if ($wasDefault) {
            $next = $user->connections()
                ->where('provider', $provider)
                ->where('enabled', true)
                ->first();
            $next?->update(['is_default' => true]);
        }
    }

    public function setDefaultIntegrationConnection(int $connectionId): void
    {
        $user = Auth::user();

        $connection = $user->connections()->find($connectionId);
        if ($connection === null) {
            return;
        }

        $user->connections()
            ->where('provider', $connection->provider)
            ->update(['is_default' => false]);

        $connection->update(['is_default' => true]);
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

    public function enableInboundEmail(): void
    {
        $user = Auth::user();

        if ($user->hasInboundEmailEnabled()) {
            return;
        }

        $user->update(['inbound_email_token' => $this->generateInboundToken()]);

        $this->inboundEmailSaved = true;
    }

    public function regenerateInboundEmail(): void
    {
        Auth::user()->update(['inbound_email_token' => $this->generateInboundToken()]);

        $this->inboundEmailSaved = true;
    }

    public function disableInboundEmail(): void
    {
        Auth::user()->update(['inbound_email_token' => null]);

        $this->inboundEmailSaved = true;
    }

    private function generateInboundToken(): string
    {
        // 32 lowercase-alphanum chars = ~160 bits of entropy.
        return bin2hex(random_bytes(16));
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

        $integrationConnections = $user->connections()
            ->whereIn('provider', [
                Connection::PROVIDER_GITHUB,
                Connection::PROVIDER_GITLAB,
                Connection::PROVIDER_JIRA,
                Connection::PROVIDER_GOOGLE,
            ])
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get()
            ->groupBy('provider');

        return view('livewire.settings', [
            'avatarUrl' => $this->resolveAvatarUrl(),
            'tools' => $tools->groupBy('category'),
            'googleConnected' => $user->hasGoogleConnected(),
            'telegramLinked' => $user->hasTelegramLinked(),
            'gitlabConnections' => $integrationConnections->get(Connection::PROVIDER_GITLAB, collect()),
            'githubConnections' => $integrationConnections->get(Connection::PROVIDER_GITHUB, collect()),
            'jiraConnections' => $integrationConnections->get(Connection::PROVIDER_JIRA, collect()),
            'googleConnections' => $integrationConnections->get(Connection::PROVIDER_GOOGLE, collect()),
        ]);
    }

    private function resolveAvatarUrl(): ?string
    {
        $path = Auth::user()->persona?->avatar_path;

        return $path ? asset('storage/'.$path) : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Ai\Services\ToolRegistry;
use App\Jobs\RunWorkflowJob;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Workflows extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $viewingRunsForId = null;

    #[Validate('required|string|max:120')]
    public string $name = '';

    public string $description = '';

    public bool $enabled = true;

    #[Validate('required|string|max:8000')]
    public string $prompt = '';

    /** @var array<int, string> */
    public array $selectedTools = [];

    public string $deliveryChannel = 'notification';

    public string $triggerType = 'schedule';

    public string $cronExpression = '';

    public string $cronPreset = '';

    public string $messageChannel = 'whatsapp';

    public string $messagePattern = '';

    public ?string $webhookToken = null;

    public string $successMessage = '';

    public string $errorMessage = '';

    /** @var array<string, string> */
    public array $cronPresets = [
        'every_minute' => '* * * * *',
        'every_hour' => '0 * * * *',
        'every_day_8am' => '0 8 * * *',
        'weekdays_8am' => '0 8 * * 1-5',
        'every_monday_9am' => '0 9 * * 1',
        'every_friday_5pm' => '0 17 * * 5',
    ];

    public function render(): View
    {
        /** @var Collection<int, Workflow> $workflows */
        $workflows = Workflow::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        $tools = app(ToolRegistry::class)
            ->allWithSettings(Auth::user())
            ->groupBy('category');

        $runs = collect();
        if ($this->viewingRunsForId !== null) {
            $runs = WorkflowRun::query()
                ->whereHas('workflow', fn ($q) => $q->where('user_id', Auth::id()))
                ->where('workflow_id', $this->viewingRunsForId)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
        }

        return view('livewire.workflows', [
            'workflows' => $workflows,
            'toolsByCategory' => $tools,
            'runs' => $runs,
        ]);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $workflow = $this->findWorkflowOrFail($id);

        $this->editingId = $workflow->id;
        $this->name = $workflow->name;
        $this->description = (string) $workflow->description;
        $this->enabled = (bool) $workflow->enabled;
        $this->prompt = $workflow->prompt;
        $this->selectedTools = $workflow->enabled_tool_names ?? [];
        $this->deliveryChannel = $workflow->delivery_channel;
        $this->triggerType = $workflow->trigger_type;
        $this->cronExpression = (string) $workflow->cron_expression;
        $this->messageChannel = $workflow->message_channel ?? 'whatsapp';
        $this->messagePattern = (string) $workflow->message_trigger_pattern;
        $this->webhookToken = $workflow->webhook_token;
        $this->showForm = true;
    }

    public function applyCronPreset(string $preset): void
    {
        if (isset($this->cronPresets[$preset])) {
            $this->cronExpression = $this->cronPresets[$preset];
            $this->cronPreset = $preset;
        }
    }

    public function save(): void
    {
        $this->errorMessage = '';
        $this->validate();

        $data = [
            'user_id' => Auth::id(),
            'name' => $this->name,
            'description' => $this->description !== '' ? $this->description : null,
            'enabled' => $this->enabled,
            'prompt' => $this->prompt,
            'enabled_tool_names' => array_values($this->selectedTools),
            'delivery_channel' => $this->deliveryChannel,
            'trigger_type' => $this->triggerType,
            'cron_expression' => null,
            'message_channel' => null,
            'message_trigger_pattern' => null,
        ];

        if ($this->triggerType === 'schedule') {
            if (trim($this->cronExpression) === '' || ! $this->isValidCron($this->cronExpression)) {
                $this->errorMessage = 'Invalid cron expression. Use a valid 5-field cron or pick a preset.';

                return;
            }
            $data['cron_expression'] = trim($this->cronExpression);
        }

        if ($this->triggerType === 'message') {
            if (trim($this->messagePattern) === '') {
                $this->errorMessage = 'Message pattern is required for message-triggered workflows.';

                return;
            }
            if (! $this->isValidPattern($this->messagePattern)) {
                $this->errorMessage = 'Invalid regex pattern.';

                return;
            }
            $data['message_channel'] = $this->messageChannel;
            $data['message_trigger_pattern'] = $this->messagePattern;
        }

        if ($this->triggerType === 'webhook') {
            $data['webhook_token'] = $this->editingId
                ? ($this->webhookToken ?: $this->generateToken())
                : $this->generateToken();
        }

        if ($this->editingId) {
            $workflow = $this->findWorkflowOrFail($this->editingId);
            $workflow->update($data);
        } else {
            $workflow = Workflow::create($data);
        }

        $this->successMessage = 'Workflow saved.';
        $this->showForm = false;
        $this->resetForm();
    }

    public function toggleEnabled(int $id): void
    {
        $workflow = $this->findWorkflowOrFail($id);
        $workflow->update(['enabled' => ! $workflow->enabled]);
    }

    public function runNow(int $id): void
    {
        $workflow = $this->findWorkflowOrFail($id);

        RunWorkflowJob::dispatch($workflow->id, 'manual');

        $this->successMessage = "Dispatched workflow: {$workflow->name}";
    }

    public function delete(int $id): void
    {
        $workflow = $this->findWorkflowOrFail($id);
        $workflow->delete();

        $this->successMessage = 'Workflow deleted.';
        if ($this->viewingRunsForId === $id) {
            $this->viewingRunsForId = null;
        }
    }

    public function viewRuns(int $id): void
    {
        $this->findWorkflowOrFail($id);
        $this->viewingRunsForId = $id;
    }

    public function closeRuns(): void
    {
        $this->viewingRunsForId = null;
    }

    public function regenerateWebhookToken(): void
    {
        $this->webhookToken = $this->generateToken();
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function findWorkflowOrFail(int $id): Workflow
    {
        return Workflow::query()
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->enabled = true;
        $this->prompt = '';
        $this->selectedTools = [];
        $this->deliveryChannel = 'notification';
        $this->triggerType = 'schedule';
        $this->cronExpression = '';
        $this->cronPreset = '';
        $this->messageChannel = 'whatsapp';
        $this->messagePattern = '';
        $this->webhookToken = null;
        $this->errorMessage = '';
    }

    private function isValidCron(string $expression): bool
    {
        try {
            new CronExpression($expression);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function isValidPattern(string $pattern): bool
    {
        if (strlen($pattern) > 255) {
            return false;
        }

        // Regex pattern (starts with '/' and has another '/' somewhere after position 0) — validate it compiles
        if (str_starts_with($pattern, '/') && strlen($pattern) >= 3 && strrpos($pattern, '/') > 0) {
            return @preg_match($pattern, '') !== false;
        }

        // Prefix match — anything non-empty is fine
        return true;
    }

    private function generateToken(): string
    {
        return (string) Str::uuid().bin2hex(random_bytes(14));
    }
}

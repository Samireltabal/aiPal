<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\GenerateAvatarJob;
use App\Services\PersonaGenerator;
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

    public string $systemPrompt = '';

    public bool $regenerating = false;

    public bool $saved = false;

    #[Validate('nullable|file|mimes:json|max:512')]
    public mixed $importFile = null;

    public string $importError = '';

    public bool $importSuccess = false;

    public bool $generatingAvatar = false;

    public bool $avatarQueued = false;

    public function mount(): void
    {
        $persona = Auth::user()->persona;

        if ($persona) {
            $this->assistantName = $persona->assistant_name;
            $this->tone = $persona->tone;
            $this->formality = $persona->formality;
            $this->humorLevel = $persona->humor_level;
            $this->backstory = $persona->backstory ?? '';
            $this->systemPrompt = $persona->system_prompt;
        }
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
            'system_prompt' => $this->systemPrompt,
        ]);

        $this->saved = true;
        $this->dispatch('saved');
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
        return view('livewire.settings', [
            'avatarUrl' => $this->resolveAvatarUrl(),
        ]);
    }

    private function resolveAvatarUrl(): ?string
    {
        $path = Auth::user()->persona?->avatar_path;

        return $path ? asset('storage/'.$path) : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Persona;
use App\Services\PersonaGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.onboarding')]
class Onboarding extends Component
{
    public int $step = 1;

    public int $totalSteps = 3;

    #[Validate('required|string|max:60')]
    public string $assistantName = 'Pal';

    #[Validate('required|in:friendly,professional,enthusiastic,calm,direct')]
    public string $tone = 'friendly';

    #[Validate('required|in:casual,semi-formal,formal')]
    public string $formality = 'casual';

    #[Validate('required|in:none,light,moderate,frequent')]
    public string $humorLevel = 'moderate';

    #[Validate('nullable|string|max:500')]
    public string $backstory = '';

    public bool $generating = false;

    public function mount(): void
    {
        // Redirect to chat if persona already exists
        if (Auth::user()->persona) {
            $this->redirect(route('dashboard'));
        }
    }

    public function next(): void
    {
        $this->validateStep();

        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    public function previous(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function finish(): void
    {
        $this->validateStep();
        $this->generating = true;

        $generator = new PersonaGenerator;

        $systemPrompt = $generator->generate([
            'assistant_name' => $this->assistantName,
            'tone' => $this->tone,
            'formality' => $this->formality,
            'humor_level' => $this->humorLevel,
            'backstory' => $this->backstory ?: 'A general-purpose personal assistant',
        ]);

        Persona::create([
            'user_id' => Auth::id(),
            'assistant_name' => $this->assistantName,
            'tone' => $this->tone,
            'formality' => $this->formality,
            'humor_level' => $this->humorLevel,
            'backstory' => $this->backstory,
            'system_prompt' => $systemPrompt,
        ]);

        $this->redirect(route('dashboard'));
    }

    private function validateStep(): void
    {
        match ($this->step) {
            1 => $this->validateOnly('assistantName'),
            2 => $this->validateStep2(),
            3 => $this->validateOnly('backstory'),
            default => null,
        };
    }

    private function validateStep2(): void
    {
        $this->validateOnly('tone');
        $this->validateOnly('formality');
        $this->validateOnly('humorLevel');
    }

    public function render(): View
    {
        return view('livewire.onboarding');
    }
}

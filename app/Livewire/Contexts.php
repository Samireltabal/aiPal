<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Context;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Contexts extends Component
{
    #[Validate('required|in:work,freelance,personal')]
    public string $newKind = 'work';

    #[Validate('required|string|max:60')]
    public string $newName = '';

    #[Validate('required|regex:/^#[0-9a-fA-F]{6,8}$/')]
    public string $newColor = '#6366f1';

    public bool $saved = false;

    public ?string $errorMessage = null;

    /**
     * Per-context input field for adding sender-domain rules.
     * Keyed by context id; bound via wire:model in the blade.
     *
     * @var array<int, string>
     */
    public array $newDomainsByContext = [];

    public function render(): View
    {
        $user = Auth::user();

        return view('livewire.contexts', [
            'contexts' => $user->contexts()
                ->orderByDesc('is_default')
                ->orderBy('kind')
                ->orderBy('name')
                ->get(),
            'connections' => $user->connections()->with('context')->get(),
        ]);
    }

    public function createContext(): void
    {
        $this->validate();

        $user = Auth::user();
        $baseSlug = Str::slug($this->newName) ?: 'context';
        $slug = $baseSlug;
        $n = 1;

        while ($user->contexts()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.++$n;
        }

        Context::create([
            'user_id' => $user->id,
            'kind' => $this->newKind,
            'name' => $this->newName,
            'slug' => $slug,
            'color' => $this->newColor,
            'is_default' => ! $user->contexts()->exists(),
        ]);

        $this->newName = '';
        $this->saved = true;
    }

    public function setDefault(int $contextId): void
    {
        $user = Auth::user();
        $target = $user->contexts()->findOrFail($contextId);

        $user->contexts()->update(['is_default' => false]);
        $target->update(['is_default' => true]);
    }

    public function archive(int $contextId): void
    {
        $user = Auth::user();
        $target = $user->contexts()->findOrFail($contextId);

        if ($target->is_default) {
            $this->errorMessage = "Can't archive your default context. Set another as default first.";

            return;
        }

        $target->update(['archived_at' => now()]);
    }

    public function unarchive(int $contextId): void
    {
        Auth::user()->contexts()->findOrFail($contextId)->update(['archived_at' => null]);
    }

    public function moveConnection(int $connectionId, int $contextId): void
    {
        $user = Auth::user();
        $connection = $user->connections()->findOrFail($connectionId);
        $context = $user->contexts()->findOrFail($contextId);

        $connection->update(['context_id' => $context->id]);
    }

    public function addInferenceRule(int $contextId): void
    {
        $domain = trim((string) ($this->newDomainsByContext[$contextId] ?? ''));
        if ($domain === '') {
            return;
        }

        $domain = ltrim(strtolower($domain), '@');
        if (! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
            $this->errorMessage = 'Domain must look like "example.com" or "@example.com".';

            return;
        }

        $user = Auth::user();
        $context = $user->contexts()->findOrFail($contextId);

        $rules = is_array($context->inference_rules) ? $context->inference_rules : [];

        // De-dupe — same domain, same context, same type is a no-op.
        foreach ($rules as $rule) {
            if (($rule['type'] ?? null) === 'sender_domain'
                && strtolower((string) ($rule['value'] ?? '')) === $domain) {
                $this->newDomainsByContext[$contextId] = '';

                return;
            }
        }

        $rules[] = [
            'type' => 'sender_domain',
            'value' => $domain,
            'priority' => 50,
        ];

        $context->update(['inference_rules' => $rules]);
        $this->newDomainsByContext[$contextId] = '';
        $this->errorMessage = null;
    }

    public function removeInferenceRule(int $contextId, int $index): void
    {
        $user = Auth::user();
        $context = $user->contexts()->findOrFail($contextId);

        $rules = is_array($context->inference_rules) ? $context->inference_rules : [];
        if (! isset($rules[$index])) {
            return;
        }

        array_splice($rules, $index, 1);
        $context->update(['inference_rules' => $rules ?: null]);
    }

    public function deleteContext(int $contextId): void
    {
        $user = Auth::user();
        $target = $user->contexts()->findOrFail($contextId);

        if ($target->is_default) {
            $this->errorMessage = "Can't delete your default context.";

            return;
        }

        $target->connections()->update([
            'context_id' => $user->defaultContext()->id,
        ]);

        $target->delete();
    }
}

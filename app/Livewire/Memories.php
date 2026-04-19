<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Memory;
use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Memories extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    #[Validate('nullable|file|mimes:json|max:1024')]
    public mixed $importFile = null;

    public string $importError = '';

    public bool $importSuccess = false;

    public int $importedCount = 0;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        Memory::query()
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();
    }

    public function import(): void
    {
        $this->importError = '';
        $this->importSuccess = false;
        $this->importedCount = 0;

        $this->validateOnly('importFile');

        if (! $this->importFile) {
            $this->importError = 'Please select a JSON file.';

            return;
        }

        $contents = file_get_contents($this->importFile->getRealPath());
        $data = json_decode($contents, true);

        if (! is_array($data) || ! isset($data['memories']) || ! is_array($data['memories'])) {
            $this->importError = 'Invalid format — expected {"memories": [...]} JSON.';

            return;
        }

        $user = Auth::user();
        $embeddings = app(EmbeddingService::class);
        $count = 0;

        foreach ($data['memories'] as $item) {
            $content = trim($item['content'] ?? '');

            if ($content === '') {
                continue;
            }

            $exists = Memory::query()
                ->where('user_id', $user->id)
                ->where('content', $content)
                ->exists();

            if (! $exists) {
                $embeddings->storeMemory($user, $content, source: 'import');
                $count++;
            }
        }

        $this->importFile = null;
        $this->importedCount = $count;
        $this->importSuccess = true;
    }

    public function render(): View
    {
        $query = Auth::user()->memories()->latest();

        if ($this->search !== '') {
            $query->where('content', 'ilike', '%'.$this->search.'%');
        }

        return view('livewire.memories', [
            'memories' => $query->paginate(20),
        ]);
    }
}

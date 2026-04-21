<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\UnifiedSearchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class UnifiedSearch extends Component
{
    public bool $open = false;

    public string $query = '';

    public array $results = [];

    public bool $searching = false;

    #[On('search:open')]
    public function openModal(): void
    {
        $this->open = true;
        $this->query = '';
        $this->results = [];
    }

    public function close(): void
    {
        $this->open = false;
        $this->query = '';
        $this->results = [];
    }

    public function updatedQuery(): void
    {
        if (strlen(trim($this->query)) < 2) {
            $this->results = [];

            return;
        }

        $this->searching = true;

        try {
            $service = app(UnifiedSearchService::class);
            $this->results = $service->search(Auth::user(), $this->query);
        } finally {
            $this->searching = false;
        }
    }

    public function render(): View
    {
        return view('livewire.unified-search');
    }
}

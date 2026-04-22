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
        $this->searching = false;
    }

    public function close(): void
    {
        $this->open = false;
        $this->query = '';
        $this->results = [];
        $this->searching = false;
    }

    public function updatedQuery(): void
    {
        $query = trim($this->query);

        if (strlen($query) < 2) {
            $this->results = [];
            $this->searching = false;

            return;
        }

        $this->searching = true;
        $snapshot = $this->query;

        try {
            $service = app(UnifiedSearchService::class);
            $results = $service->search(Auth::user(), $query);

            if ($this->query === $snapshot) {
                $this->results = $results;
            }
        } finally {
            if ($this->query === $snapshot) {
                $this->searching = false;
            }
        }
    }

    public function render(): View
    {
        return view('livewire.unified-search');
    }
}

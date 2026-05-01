<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Person;
use App\Models\PersonEmail;
use App\Modules\People\Services\PersonMerger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class People extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $tag = '';

    #[Url(except: false)]
    public bool $stale = false;

    public bool $showAddForm = false;

    #[Validate('required|string|max:255')]
    public string $newName = '';

    #[Validate('nullable|email|max:255')]
    public string $newEmail = '';

    #[Validate('nullable|string|max:255')]
    public string $newCompany = '';

    public string $successMessage = '';

    /** @var array<int,bool> */
    public array $selected = [];

    public string $bulkTag = '';

    public int $mergePrimaryId = 0;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTag(): void
    {
        $this->resetPage();
    }

    public function updatingStale(): void
    {
        $this->resetPage();
    }

    public function clearTag(): void
    {
        $this->tag = '';
        $this->resetPage();
    }

    public function setTag(string $tag): void
    {
        $this->tag = $tag;
        $this->resetPage();
    }

    public function toggleAddForm(): void
    {
        $this->showAddForm = ! $this->showAddForm;
        if (! $this->showAddForm) {
            $this->resetNewForm();
        }
    }

    public function createPerson(): void
    {
        $this->validate();

        $user = Auth::user();
        $email = trim(strtolower($this->newEmail));

        DB::transaction(function () use ($user, $email): void {
            $person = Person::create([
                'user_id' => $user->id,
                'context_id' => $user->currentContext()?->id ?? $user->defaultContext()?->id,
                'display_name' => trim($this->newName),
                'company' => trim($this->newCompany) !== '' ? trim($this->newCompany) : null,
                'tags' => [],
                'custom' => [],
            ]);

            if ($email !== '') {
                PersonEmail::firstOrCreate(
                    ['user_id' => $user->id, 'email' => $email],
                    ['person_id' => $person->id, 'is_primary' => true],
                );
            }
        });

        $this->resetNewForm();
        $this->showAddForm = false;
        $this->successMessage = 'Person added.';
        $this->resetPage();
    }

    public function applyBulkTag(): void
    {
        $tag = trim($this->bulkTag);
        if ($tag === '') {
            $this->successMessage = 'Enter a tag to apply.';

            return;
        }

        $ids = array_keys(array_filter($this->selected));
        if ($ids === []) {
            $this->successMessage = 'Select at least one person.';

            return;
        }

        $people = Person::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $ids)
            ->get();

        $count = 0;
        foreach ($people as $person) {
            $existing = $person->tags ?? [];
            if (in_array($tag, $existing, true)) {
                continue;
            }
            $person->update(['tags' => array_values(array_merge($existing, [$tag]))]);
            $count++;
        }

        $this->bulkTag = '';
        $this->selected = [];
        $this->successMessage = "Tag applied to {$count} ".($count === 1 ? 'person' : 'people').'.';
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->mergePrimaryId = 0;
    }

    public function startMerge(): void
    {
        $ids = array_keys(array_filter($this->selected));
        if (count($ids) !== 2) {
            $this->successMessage = 'Select exactly 2 people to merge.';

            return;
        }

        $this->mergePrimaryId = (int) $ids[0];
    }

    public function cancelMerge(): void
    {
        $this->mergePrimaryId = 0;
    }

    public function setMergePrimary(int $id): void
    {
        if (! isset($this->selected[$id]) || ! $this->selected[$id]) {
            return;
        }

        $this->mergePrimaryId = $id;
    }

    public function confirmMerge(PersonMerger $merger): void
    {
        $ids = array_keys(array_filter($this->selected));
        if (count($ids) !== 2 || $this->mergePrimaryId === 0) {
            $this->successMessage = 'Invalid merge selection.';

            return;
        }

        if (! in_array($this->mergePrimaryId, $ids, true)) {
            $this->successMessage = 'Primary must be one of the selected people.';

            return;
        }

        $userId = Auth::id();
        $primary = Person::query()->where('user_id', $userId)->find($this->mergePrimaryId);
        $duplicateId = $ids[0] === $this->mergePrimaryId ? $ids[1] : $ids[0];
        $duplicate = Person::query()->where('user_id', $userId)->find($duplicateId);

        if ($primary === null || $duplicate === null) {
            $this->successMessage = 'One of the selected people no longer exists.';
            $this->clearSelection();

            return;
        }

        $merger->merge($primary, $duplicate);

        $this->clearSelection();
        $this->successMessage = 'Merged into '.$primary->display_name.'.';
        $this->resetPage();
    }

    public function exportJson(): StreamedResponse
    {
        $people = $this->buildExportQuery()->get();
        $payload = $people->map(fn (Person $p) => [
            'display_name' => $p->display_name,
            'company' => $p->company,
            'title' => $p->title,
            'notes' => $p->notes,
            'tags' => $p->tags ?? [],
            'birthday' => $p->birthday?->toDateString(),
            'last_contact_at' => $p->last_contact_at?->toIso8601String(),
            'emails' => $p->emails->pluck('email')->all(),
            'phones' => $p->phones->pluck('phone')->all(),
        ])->all();

        $filename = 'people-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function exportCsv(): StreamedResponse
    {
        $people = $this->buildExportQuery()->get();
        $filename = 'people-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($people): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['display_name', 'company', 'title', 'tags', 'emails', 'phones', 'birthday', 'last_contact_at', 'notes']);
            foreach ($people as $p) {
                fputcsv($out, [
                    $p->display_name,
                    $p->company,
                    $p->title,
                    implode('; ', $p->tags ?? []),
                    $p->emails->pluck('email')->implode('; '),
                    $p->phones->pluck('phone')->implode('; '),
                    $p->birthday?->toDateString(),
                    $p->last_contact_at?->toIso8601String(),
                    $p->notes,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function buildExportQuery()
    {
        return Person::query()
            ->where('user_id', Auth::id())
            ->with([
                'emails' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('id'),
                'phones' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('id'),
            ])
            ->orderBy('display_name');
    }

    private function resetNewForm(): void
    {
        $this->newName = '';
        $this->newEmail = '';
        $this->newCompany = '';
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $userId = Auth::id();

        $query = Person::query()
            ->where('user_id', $userId)
            ->with(['emails' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('id')]);

        if (($q = trim($this->search)) !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], strtolower($q)).'%';
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($w) use ($like, $likeOp): void {
                $w->whereRaw('LOWER(display_name) '.$likeOp.' ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(company, \'\')) '.$likeOp.' ?', [$like])
                    ->orWhereExists(function ($sub) use ($like, $likeOp): void {
                        $sub->select(DB::raw(1))
                            ->from('person_emails')
                            ->whereColumn('person_emails.person_id', 'people.id')
                            ->whereRaw('LOWER(email) '.$likeOp.' ?', [$like]);
                    });
            });
        }

        if ($this->tag !== '') {
            $query->whereJsonContains('tags', $this->tag);
        }

        if ($this->stale) {
            $threshold = now()->subDays((int) config('people.staleness_days', 90));
            $query->where(function ($w) use ($threshold): void {
                $w->whereNull('last_contact_at')->orWhere('last_contact_at', '<', $threshold);
            });
        }

        $query
            ->orderByRaw('last_contact_at IS NULL')
            ->orderByDesc('last_contact_at')
            ->orderBy('display_name');

        $allTags = Person::query()
            ->where('user_id', $userId)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->all();

        $selectedIds = array_keys(array_filter($this->selected));
        $selectedPeople = $selectedIds === [] ? collect() : Person::query()
            ->where('user_id', $userId)
            ->whereIn('id', $selectedIds)
            ->get(['id', 'display_name', 'company']);

        return view('livewire.people', [
            'people' => $query->paginate(30),
            'allTags' => $allTags,
            'stalenessDays' => (int) config('people.staleness_days', 90),
            'selectedPeople' => $selectedPeople,
        ]);
    }
}

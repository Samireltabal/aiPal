<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Person;
use App\Models\PersonEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

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

        return view('livewire.people', [
            'people' => $query->paginate(30),
            'allTags' => $allTags,
            'stalenessDays' => (int) config('people.staleness_days', 90),
        ]);
    }
}

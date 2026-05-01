<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Interaction;
use App\Models\Person;
use App\Models\PersonEmail;
use App\Models\PersonPhone;
use App\Modules\People\Services\InteractionRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class PersonDetail extends Component
{
    public int $personId;

    public string $displayName = '';

    public string $company = '';

    public string $title = '';

    public string $notes = '';

    public string $tagsInput = '';

    public string $birthday = '';

    public string $newEmail = '';

    public string $newPhone = '';

    // — Log interaction form —
    public string $logChannel = 'meeting';

    public string $logDirection = 'none';

    #[Validate('required|date')]
    public string $logOccurredAt = '';

    public string $logSubject = '';

    public string $logSummary = '';

    public ?int $expandedInteractionId = null;

    public string $successMessage = '';

    public function mount(int $id): void
    {
        $person = $this->loadPerson($id);
        $this->personId = $person->id;
        $this->displayName = $person->display_name ?? '';
        $this->company = $person->company ?? '';
        $this->title = $person->title ?? '';
        $this->notes = $person->notes ?? '';
        $this->tagsInput = implode(', ', $person->tags ?? []);
        $this->birthday = $person->birthday?->toDateString() ?? '';
        $this->logOccurredAt = now()->format('Y-m-d\TH:i');
    }

    private function loadPerson(int $id): Person
    {
        return Person::query()
            ->where('user_id', Auth::id())
            ->with(['emails' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('id'),
                'phones' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('id'),
            ])
            ->findOrFail($id);
    }

    public function saveProfile(): void
    {
        $person = $this->loadPerson($this->personId);

        $tags = collect(explode(',', $this->tagsInput))
            ->map(fn (string $t) => trim($t))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $person->update([
            'display_name' => trim($this->displayName) !== '' ? trim($this->displayName) : $person->display_name,
            'company' => trim($this->company) !== '' ? trim($this->company) : null,
            'title' => trim($this->title) !== '' ? trim($this->title) : null,
            'notes' => trim($this->notes) !== '' ? $this->notes : null,
            'tags' => $tags,
            'birthday' => $this->birthday !== '' ? $this->birthday : null,
        ]);

        $this->tagsInput = implode(', ', $tags);
        $this->successMessage = 'Profile updated.';
    }

    public function addEmail(): void
    {
        $email = strtolower(trim($this->newEmail));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('newEmail', 'Invalid email.');

            return;
        }

        $person = $this->loadPerson($this->personId);

        PersonEmail::firstOrCreate(
            ['user_id' => $person->user_id, 'email' => $email],
            ['person_id' => $person->id, 'is_primary' => $person->emails->isEmpty()],
        );

        $this->newEmail = '';
    }

    public function deleteEmail(int $id): void
    {
        PersonEmail::query()
            ->where('user_id', Auth::id())
            ->where('person_id', $this->personId)
            ->where('id', $id)
            ->delete();
    }

    public function makeEmailPrimary(int $id): void
    {
        $userId = Auth::id();
        PersonEmail::query()->where('user_id', $userId)->where('person_id', $this->personId)->update(['is_primary' => false]);
        PersonEmail::query()->where('user_id', $userId)->where('person_id', $this->personId)->where('id', $id)->update(['is_primary' => true]);
    }

    public function addPhone(): void
    {
        $phone = trim($this->newPhone);
        if ($phone === '') {
            return;
        }

        $person = $this->loadPerson($this->personId);

        PersonPhone::firstOrCreate(
            ['user_id' => $person->user_id, 'phone' => $phone],
            ['person_id' => $person->id, 'is_primary' => $person->phones->isEmpty()],
        );

        $this->newPhone = '';
    }

    public function deletePhone(int $id): void
    {
        PersonPhone::query()
            ->where('user_id', Auth::id())
            ->where('person_id', $this->personId)
            ->where('id', $id)
            ->delete();
    }

    public function makePhonePrimary(int $id): void
    {
        $userId = Auth::id();
        PersonPhone::query()->where('user_id', $userId)->where('person_id', $this->personId)->update(['is_primary' => false]);
        PersonPhone::query()->where('user_id', $userId)->where('person_id', $this->personId)->where('id', $id)->update(['is_primary' => true]);
    }

    public function logInteraction(InteractionRecorder $recorder): void
    {
        $this->validate([
            'logChannel' => 'required|string|in:'.implode(',', Interaction::CHANNELS),
            'logDirection' => 'required|string|in:'.implode(',', Interaction::DIRECTIONS),
            'logOccurredAt' => 'required|date',
            'logSubject' => 'nullable|string|max:512',
            'logSummary' => 'nullable|string|max:2000',
        ]);

        $person = $this->loadPerson($this->personId);

        $recorder->record($person, [
            'channel' => $this->logChannel,
            'direction' => $this->logDirection,
            'occurred_at' => $this->logOccurredAt,
            'subject' => trim($this->logSubject) !== '' ? trim($this->logSubject) : null,
            'summary' => trim($this->logSummary) !== '' ? trim($this->logSummary) : null,
            'metadata' => ['source' => 'manual'],
        ]);

        $this->logSubject = '';
        $this->logSummary = '';
        $this->logOccurredAt = now()->format('Y-m-d\TH:i');
        $this->successMessage = 'Interaction logged.';
    }

    public function toggleInteraction(int $id): void
    {
        $this->expandedInteractionId = $this->expandedInteractionId === $id ? null : $id;
    }

    public function deletePerson(): void
    {
        $person = $this->loadPerson($this->personId);
        $person->delete();
        $this->redirect(route('people'), navigate: true);
    }

    public function render(): View
    {
        $person = $this->loadPerson($this->personId);

        $interactions = Interaction::query()
            ->where('user_id', Auth::id())
            ->where('person_id', $this->personId)
            ->orderByDesc('occurred_at')
            ->limit(100)
            ->get();

        $primaryEmail = $person->emails->first()?->email;
        $gravatar = $primaryEmail
            ? 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($primaryEmail))).'?d=mp&s=120'
            : null;

        return view('livewire.person-detail', [
            'person' => $person,
            'interactions' => $interactions,
            'avatarUrl' => $person->photo_url ?: $gravatar,
            'channels' => Interaction::CHANNELS,
            'directions' => Interaction::DIRECTIONS,
        ]);
    }
}

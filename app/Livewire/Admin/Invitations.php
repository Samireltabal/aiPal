<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Invitations extends Component
{
    #[Validate('nullable|email')]
    public string $email = '';

    public string $newInviteUrl = '';

    public function createInvitation(): void
    {
        $this->validate();

        /** @var User $user */
        $user = Auth::user();

        $invitation = Invitation::create([
            'token' => Str::random(64),
            'email' => $this->email ?: null,
            'created_by' => $user->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->newInviteUrl = route('register', ['token' => $invitation->token]);
        $this->email = '';
    }

    public function revokeInvitation(int $id): void
    {
        /** @var User $user */
        $user = Auth::user();

        Invitation::where('id', $id)
            ->where('created_by', $user->id)
            ->whereNull('accepted_at')
            ->delete();
    }

    public function render(): View
    {
        $invitations = Invitation::with('creator')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.admin.invitations', compact('invitations'));
    }
}

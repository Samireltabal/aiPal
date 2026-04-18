<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.auth')]
class Register extends Component
{
    #[Url]
    public string $token = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public ?Invitation $invitation = null;

    public function mount(): void
    {
        $this->invitation = Invitation::where('token', $this->token)->first();

        if (! $this->invitation || ! $this->invitation->isPending()) {
            abort(403, 'This invitation is invalid or has expired.');
        }

        if ($this->invitation->email) {
            $this->email = $this->invitation->email;
        }
    }

    public function register(): void
    {
        $this->validate();

        if (! $this->invitation || ! $this->invitation->isPending()) {
            $this->addError('token', 'This invitation is no longer valid.');

            return;
        }

        if ($this->invitation->email && $this->invitation->email !== $this->email) {
            $this->addError('email', 'This invitation was issued for a different email address.');

            return;
        }

        $isFirstUser = User::count() === 0;

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'is_admin' => $isFirstUser,
        ]);

        $this->invitation->accept();

        Auth::login($user);
        session()->regenerate();

        $this->redirect(route('chat'));
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}

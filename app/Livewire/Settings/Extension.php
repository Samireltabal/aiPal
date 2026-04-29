<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Extension extends Component
{
    public ?string $generatedToken = null;

    public ?string $connectLink = null;

    public function generate(): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        // One active extension token at a time keeps revocation simple and
        // prevents stale leaked tokens from accumulating in chrome.storage.sync.
        $this->revokeExistingExtensionTokens($user);

        $token = $user->createToken('extension', ['extension'])->plainTextToken;

        $this->generatedToken = $token;
        $this->connectLink = $this->buildConnectLink($token);
    }

    public function revoke(int $tokenId): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $user->tokens()->where('id', $tokenId)->where('name', 'extension')->delete();

        $this->generatedToken = null;
        $this->connectLink = null;
    }

    public function render(): View
    {
        $tokens = Auth::user()
            ?->tokens()
            ->where('name', 'extension')
            ->orderByDesc('id')
            ->get(['id', 'name', 'last_used_at', 'created_at'])
            ?? collect();

        return view('livewire.settings.extension', [
            'tokens' => $tokens,
        ]);
    }

    private function revokeExistingExtensionTokens(User $user): void
    {
        $user->tokens()->where('name', 'extension')->delete();
    }

    private function buildConnectLink(string $token): string
    {
        $host = rtrim(URL::to('/'), '/');

        return 'aipal-ext://connect?host='.urlencode($host).'&token='.urlencode($token);
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Chat extends Component
{
    public string $message = '';

    /** @var array<int, array{id: string, title: string}> */
    public array $conversations = [];

    public ?string $activeConversationId = null;

    public string $apiToken = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $existing = $user->tokens()->where('name', 'web-session')->first();
        if ($existing) {
            $existing->delete();
        }

        $this->apiToken = $user->createToken('web-session')->plainTextToken;
        $this->loadConversations();
    }

    public function loadConversations(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $conversations = DB::table('agent_conversations')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'title', 'created_at']);

        $this->conversations = $conversations->map(fn ($c) => [
            'id' => $c->id,
            'title' => $c->title ?? 'Untitled',
        ])->toArray();
    }

    public function selectConversation(string $id): void
    {
        $this->activeConversationId = $id;
    }

    public function newConversation(): void
    {
        $this->activeConversationId = null;
    }

    public function deleteConversation(string $id): void
    {
        /** @var User $user */
        $user = Auth::user();

        DB::table('agent_conversations')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

        if ($this->activeConversationId === $id) {
            $this->activeConversationId = null;
        }

        $this->loadConversations();
    }

    public function generateToken(): string
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->createToken('chat-token')->plainTextToken;
    }

    public function render(): View
    {
        return view('livewire.chat');
    }
}

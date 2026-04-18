<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InviteCommand extends Command
{
    protected $signature = 'invite {email? : Email to restrict invitation to}';

    protected $description = 'Generate an invitation link (first user to register via the link becomes admin)';

    public function handle(): int
    {
        $email = $this->argument('email');

        $admin = User::where('is_admin', true)->first();

        $invitation = Invitation::create([
            'token' => Str::random(64),
            'email' => $email ?: null,
            'created_by' => $admin?->id,
            'expires_at' => now()->addDays(7),
        ]);

        $url = url(route('register', ['token' => $invitation->token], false));

        $this->newLine();
        $this->line('  <fg=green>Invitation created!</>');
        $this->line('  <fg=cyan>URL:</>    '.$url);

        if ($email) {
            $this->line('  <fg=cyan>For:</>    '.$email);
        }

        $this->line('  <fg=cyan>Expires:</> '.$invitation->expires_at->format('Y-m-d H:i'));
        $this->newLine();

        return self::SUCCESS;
    }
}

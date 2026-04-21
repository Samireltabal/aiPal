<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'is_admin', 'briefing_enabled', 'briefing_time', 'briefing_timezone', 'briefing_last_sent_at', 'telegram_chat_id', 'telegram_conversation_id', 'whatsapp_phone', 'whatsapp_conversation_id', 'jira_host', 'jira_email', 'jira_token', 'default_reminder_channel', 'gitlab_host', 'gitlab_token', 'github_token', 'push_notifications_enabled'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'briefing_last_sent_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'briefing_enabled' => 'boolean',
            'push_notifications_enabled' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function persona(): HasOne
    {
        return $this->hasOne(Persona::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'created_by');
    }

    public function memories(): HasMany
    {
        return $this->hasMany(Memory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function googleToken(): HasOne
    {
        return $this->hasOne(GoogleToken::class);
    }

    public function hasGoogleConnected(): bool
    {
        return $this->googleToken()->exists();
    }

    public function hasTelegramLinked(): bool
    {
        return $this->telegram_chat_id !== null;
    }

    public function hasWhatsAppLinked(): bool
    {
        return $this->whatsapp_phone !== null;
    }

    public function hasJiraConnected(): bool
    {
        return $this->jira_host !== null && $this->jira_email !== null && $this->jira_token !== null;
    }

    public function hasGitLabConnected(): bool
    {
        return $this->gitlab_token !== null;
    }

    public function hasGitHubConnected(): bool
    {
        return $this->github_token !== null;
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }
}

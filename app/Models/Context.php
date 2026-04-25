<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ContextFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'kind',
    'name',
    'slug',
    'color',
    'is_default',
    'inference_rules',
    'archived_at',
])]
class Context extends Model
{
    /** @use HasFactory<ContextFactory> */
    use HasFactory;

    public const KIND_WORK = 'work';

    public const KIND_FREELANCE = 'freelance';

    public const KIND_PERSONAL = 'personal';

    /** @var array<int, string> */
    public const KINDS = [self::KIND_WORK, self::KIND_FREELANCE, self::KIND_PERSONAL];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'inference_rules' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function memories(): HasMany
    {
        return $this->hasMany(Memory::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function inboundEmailAddress(): ?string
    {
        $connection = $this->connections()
            ->where('provider', 'inbound_email')
            ->where('enabled', true)
            ->first();

        if ($connection === null || $connection->identifier === null) {
            return null;
        }

        return "forward-{$connection->identifier}-{$this->slug}@".config('inbound.domain');
    }
}

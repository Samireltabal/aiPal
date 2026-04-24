<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'context_id',
    'provider',
    'capabilities',
    'label',
    'identifier',
    'credentials',
    'is_default',
    'enabled',
    'metadata',
    'last_synced_at',
])]
class Connection extends Model
{
    /** @use HasFactory<ConnectionFactory> */
    use HasFactory;

    public const PROVIDER_GOOGLE = 'google';

    public const PROVIDER_MICROSOFT = 'microsoft';

    public const PROVIDER_TELEGRAM = 'telegram';

    public const PROVIDER_WHATSAPP = 'whatsapp';

    public const PROVIDER_INBOUND_EMAIL = 'inbound_email';

    public const PROVIDER_JIRA = 'jira';

    public const PROVIDER_GITLAB = 'gitlab';

    public const PROVIDER_GITHUB = 'github';

    public const CAPABILITY_MAIL = 'mail';

    public const CAPABILITY_CALENDAR = 'calendar';

    public const CAPABILITY_CHAT = 'chat';

    public const CAPABILITY_ISSUES = 'issues';

    public const CAPABILITY_CODE = 'code';

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'credentials' => 'encrypted:array',
            'metadata' => 'array',
            'is_default' => 'boolean',
            'enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function context(): BelongsTo
    {
        return $this->belongsTo(Context::class);
    }

    public function isCapableOf(string $capability): bool
    {
        return in_array($capability, (array) $this->capabilities, true);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeCapability($query, string $capability)
    {
        return $query->whereJsonContains('capabilities', $capability);
    }
}

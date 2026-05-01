<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InteractionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    /** @use HasFactory<InteractionFactory> */
    use HasFactory;

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_TELEGRAM = 'telegram';

    public const CHANNEL_MEETING = 'meeting';

    public const CHANNEL_NOTE = 'note';

    public const CHANNEL_CHAT = 'chat';

    public const CHANNELS = [
        self::CHANNEL_EMAIL,
        self::CHANNEL_WHATSAPP,
        self::CHANNEL_TELEGRAM,
        self::CHANNEL_MEETING,
        self::CHANNEL_NOTE,
        self::CHANNEL_CHAT,
    ];

    public const DIRECTION_INBOUND = 'inbound';

    public const DIRECTION_OUTBOUND = 'outbound';

    public const DIRECTION_NONE = 'none';

    public const DIRECTIONS = [
        self::DIRECTION_INBOUND,
        self::DIRECTION_OUTBOUND,
        self::DIRECTION_NONE,
    ];

    protected $fillable = [
        'person_id',
        'user_id',
        'context_id',
        'channel',
        'direction',
        'occurred_at',
        'subject',
        'summary',
        'raw_excerpt',
        'metadata',
        'external_id',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
            'embedding' => 'array',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function context(): BelongsTo
    {
        return $this->belongsTo(Context::class);
    }
}

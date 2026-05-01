<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'user_id',
        'context_id',
        'display_name',
        'given_name',
        'family_name',
        'nickname',
        'company',
        'title',
        'notes',
        'tags',
        'custom',
        'birthday',
        'photo_url',
        'embedding',
        'last_contact_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'custom' => 'array',
            'embedding' => 'array',
            'birthday' => 'date',
            'last_contact_at' => 'datetime',
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

    public function emails(): HasMany
    {
        return $this->hasMany(PersonEmail::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(PersonPhone::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function primaryEmail(): ?string
    {
        return $this->emails()->orderByDesc('is_primary')->orderBy('id')->value('email');
    }

    public function primaryPhone(): ?string
    {
        return $this->phones()->orderByDesc('is_primary')->orderBy('id')->value('phone');
    }
}

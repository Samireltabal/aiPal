<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonPhoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonPhone extends Model
{
    /** @use HasFactory<PersonPhoneFactory> */
    use HasFactory;

    protected $fillable = ['person_id', 'user_id', 'phone', 'label', 'is_primary'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
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
}

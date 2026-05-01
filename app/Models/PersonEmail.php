<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonEmailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonEmail extends Model
{
    /** @use HasFactory<PersonEmailFactory> */
    use HasFactory;

    protected $fillable = ['person_id', 'user_id', 'email', 'label', 'is_primary'];

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

    /** Always lowercased so unique(user_id, email) is case-insensitive in practice. */
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }
}

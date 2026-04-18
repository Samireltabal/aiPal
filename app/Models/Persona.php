<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'assistant_name', 'tone', 'formality', 'humor_level', 'backstory', 'system_prompt'])]
class Persona extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'tool', 'enabled'])]
class UserToolSetting extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function isEnabled(int $userId, string $toolName): bool
    {
        $setting = static::query()
            ->where('user_id', $userId)
            ->where('tool', $toolName)
            ->first();

        return $setting === null || (bool) $setting->enabled;
    }

    /** @return string[] Tool names explicitly disabled for the user. */
    public static function disabledToolsFor(int $userId): array
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('enabled', false)
            ->pluck('tool')
            ->all();
    }
}

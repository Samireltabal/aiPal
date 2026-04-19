<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'access_token', 'refresh_token', 'expires_at', 'scopes'])]
class GoogleToken extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, explode(' ', $this->scopes), true);
    }

    public function toGoogleArray(): array
    {
        return array_filter([
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_in' => $this->expires_at
                ? max(0, (int) Carbon::now()->diffInSeconds($this->expires_at, false))
                : null,
            'token_type' => 'Bearer',
        ]);
    }
}

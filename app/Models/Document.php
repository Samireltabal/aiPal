<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = ['user_id', 'name', 'file_name', 'mime_type', 'size', 'status', 'error_message'];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function markProcessing(): void
    {
        $this->update(['status' => 'processing', 'error_message' => null]);
    }

    public function markReady(): void
    {
        $this->update(['status' => 'ready', 'error_message' => null]);
    }

    public function markFailed(string $message): void
    {
        $this->update(['status' => 'failed', 'error_message' => $message]);
    }
}

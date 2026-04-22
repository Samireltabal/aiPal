<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'enabled',
        'prompt',
        'enabled_tool_names',
        'delivery_channel',
        'trigger_type',
        'cron_expression',
        'webhook_token',
        'message_channel',
        'message_trigger_pattern',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'enabled_tool_names' => 'array',
            'last_run_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    public function latestRun(): ?WorkflowRun
    {
        return $this->runs()->latest('created_at')->first();
    }

    /**
     * Check whether an incoming message matches this workflow's message trigger.
     */
    public function matchesMessage(string $channel, string $text): bool
    {
        if ($this->trigger_type !== 'message' || ! $this->enabled) {
            return false;
        }

        $workflowChannel = $this->message_channel ?? 'any';

        if ($workflowChannel !== 'any' && $workflowChannel !== $channel) {
            return false;
        }

        $pattern = (string) $this->message_trigger_pattern;

        if ($pattern === '') {
            return false;
        }

        if (self::looksLikeRegex($pattern)) {
            return @preg_match($pattern, $text) === 1;
        }

        return str_starts_with(trim($text), $pattern);
    }

    /**
     * A pattern is treated as regex if it starts with '/' and contains another '/'
     * somewhere after the first character (optionally followed by modifier chars).
     */
    private static function looksLikeRegex(string $pattern): bool
    {
        if (! str_starts_with($pattern, '/') || strlen($pattern) < 3) {
            return false;
        }

        $lastSlash = strrpos($pattern, '/');

        return $lastSlash !== false && $lastSlash !== 0;
    }
}

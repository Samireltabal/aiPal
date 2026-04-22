<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCurrentDateTime extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'get_current_datetime';
    }

    public static function toolLabel(): string
    {
        return 'Get Current Date & Time';
    }

    public static function toolCategory(): string
    {
        return 'utilities';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Returns the current date and time in the user\'s timezone. Use this whenever you need to know the current date, time, day of the week, or when the user asks "what time is it", "what\'s today\'s date", "what day is it", etc.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    protected function execute(Request $request): Stringable|string
    {
        $timezone = $this->user->briefing_timezone ?? 'UTC';

        $now = now()->setTimezone($timezone);

        return sprintf(
            'Current date and time: %s %s (timezone: %s)',
            $now->format('l, F j, Y'),
            $now->format('g:i A'),
            $timezone,
        );
    }
}

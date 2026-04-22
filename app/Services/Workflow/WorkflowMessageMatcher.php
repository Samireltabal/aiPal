<?php

declare(strict_types=1);

namespace App\Services\Workflow;

use App\Models\User;
use App\Models\Workflow;

class WorkflowMessageMatcher
{
    /**
     * Find the first enabled message-triggered workflow that matches this message.
     */
    public function match(User $user, string $channel, string $text): ?Workflow
    {
        $workflows = Workflow::query()
            ->where('user_id', $user->id)
            ->where('trigger_type', 'message')
            ->where('enabled', true)
            ->where(function ($q) use ($channel): void {
                $q->where('message_channel', $channel)
                    ->orWhere('message_channel', 'any');
            })
            ->get();

        foreach ($workflows as $workflow) {
            if ($workflow->matchesMessage($channel, $text)) {
                return $workflow;
            }
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function send(User $user, string $title, string $body, ?string $url = null): void
    {
        $subscriptions = $user->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $push = new WebPush([
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ]);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?? '/',
        ]);

        $stale = [];

        foreach ($subscriptions as $sub) {
            $push->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'keys' => [
                        'p256dh' => $sub->public_key,
                        'auth' => $sub->auth_token,
                    ],
                ]),
                $payload,
            );
        }

        foreach ($push->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                $stale[] = $report->getRequest()->getUri()->__toString();
            } elseif (! $report->isSuccess()) {
                Log::warning('Web push failed', ['reason' => $report->getReason()]);
            }
        }

        if (! empty($stale)) {
            PushSubscription::whereIn('endpoint', $stale)->delete();
        }
    }
}

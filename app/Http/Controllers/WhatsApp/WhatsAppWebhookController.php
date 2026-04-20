<?php

declare(strict_types=1);

namespace App\Http\Controllers\WhatsApp;

use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class WhatsAppWebhookController
{
    /**
     * Handle webhook verification challenge from Meta.
     * Meta sends a GET request when you register or update the webhook URL.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response((string) $challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming WhatsApp messages from Meta.
     */
    public function __invoke(Request $request, WhatsAppService $whatsApp): Response
    {
        if (! $this->isValidSignature($request, $whatsApp)) {
            return response('Unauthorized', 401);
        }

        $body = $request->json()->all();

        // Meta wraps messages in entry/changes structure
        $messages = data_get($body, 'entry.0.changes.0.value.messages', []);

        if (empty($messages)) {
            return response('OK', 200);
        }

        $message = $messages[0];
        $from = (string) ($message['from'] ?? '');
        $type = $message['type'] ?? '';

        if ($from === '' || ! in_array($type, ['text', 'audio'], true)) {
            return response('OK', 200);
        }

        $text = $type === 'text' ? trim(data_get($message, 'text.body', '')) : null;
        $audioMediaId = $type === 'audio' ? data_get($message, 'audio.id') : null;

        if ($text === '' && $audioMediaId === null) {
            return response('OK', 200);
        }

        if (RateLimiter::tooManyAttempts("whatsapp:{$from}", 10)) {
            $whatsApp->send($from, 'Too many messages. Please wait a minute.');

            return response('OK', 200);
        }

        RateLimiter::hit("whatsapp:{$from}", 60);

        $user = User::query()->where('whatsapp_phone', $from)->first();

        if (! $user) {
            $whatsApp->send($from, "Your number is not linked to an aiPal account.\n\nEnter *{$from}* in *Settings → WhatsApp* to link your account.");

            return response('OK', 200);
        }

        ProcessWhatsAppMessageJob::dispatch($user->id, $from, $text ?: null, $audioMediaId);

        return response('OK', 200);
    }

    private function isValidSignature(Request $request, WhatsAppService $whatsApp): bool
    {
        $signature = $request->header('X-Hub-Signature-256', '');

        if (! $signature) {
            // Allow missing signature only when app_secret is not configured (dev mode)
            return config('services.whatsapp.app_secret') === null;
        }

        return $whatsApp->verifySignature($request->getContent(), $signature);
    }
}

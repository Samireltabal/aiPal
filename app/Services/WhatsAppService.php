<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MessagingChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements MessagingChannel
{
    private string $apiBase;

    private string $accessToken;

    public function __construct()
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->apiBase = "https://graph.facebook.com/v20.0/{$phoneNumberId}";
    }

    public function send(string $recipient, string $message): void
    {
        $response = Http::timeout(10)
            ->withToken($this->accessToken)
            ->post("{$this->apiBase}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if (! $response->successful()) {
            Log::warning('WhatsApp sendMessage failed', [
                'to' => $recipient,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    public function channelName(): string
    {
        return 'whatsapp';
    }

    /**
     * Download a WhatsApp audio media file and save it to a temp path. Returns the path or null on failure.
     */
    public function downloadAudio(string $mediaId): ?string
    {
        // Step 1: resolve the download URL
        $response = Http::timeout(10)
            ->withToken($this->accessToken)
            ->get("https://graph.facebook.com/v20.0/{$mediaId}");

        if (! $response->successful()) {
            Log::warning('WhatsApp media URL fetch failed', ['media_id' => $mediaId]);

            return null;
        }

        $url = $response->json('url');

        if (! $url) {
            return null;
        }

        // Step 2: download the file
        $download = Http::timeout(30)
            ->withToken($this->accessToken)
            ->get($url);

        if (! $download->successful()) {
            Log::warning('WhatsApp media download failed', ['media_id' => $mediaId]);

            return null;
        }

        $tmpPath = sys_get_temp_dir().'/wa_audio_'.uniqid().'.ogg';
        file_put_contents($tmpPath, $download->body());

        return $tmpPath;
    }

    /**
     * Verify the webhook signature from Meta.
     * Meta signs payloads with HMAC-SHA256 using the app secret.
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        $secret = config('services.whatsapp.app_secret');

        if (! $secret) {
            return true;
        }

        $expected = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}

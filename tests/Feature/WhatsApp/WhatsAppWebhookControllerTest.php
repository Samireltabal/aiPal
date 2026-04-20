<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $from, string $body): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => $from,
                            'type' => 'text',
                            'text' => ['body' => $body],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    public function test_webhook_verification_challenge_succeeds_with_valid_token(): void
    {
        config(['services.whatsapp.verify_token' => 'my-verify-token']);

        $response = $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=my-verify-token&hub_challenge=challenge123');

        $response->assertStatus(200);
        $response->assertSee('challenge123');
    }

    public function test_webhook_verification_fails_with_wrong_token(): void
    {
        config(['services.whatsapp.verify_token' => 'my-verify-token']);

        $response = $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=challenge123');

        $response->assertStatus(403);
    }

    public function test_message_from_unlinked_number_receives_link_instructions(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        $whatsApp = $this->mock(WhatsAppService::class);
        $whatsApp->shouldReceive('verifySignature')->andReturn(true);
        $whatsApp->shouldReceive('send')
            ->once()
            ->withArgs(fn ($to, $msg) => $to === '201234567890' && str_contains($msg, '201234567890'));

        $this->postJson('/webhooks/whatsapp', $this->payload('201234567890', 'Hello'));
    }

    public function test_message_from_linked_user_dispatches_job(): void
    {
        Queue::fake();
        config(['services.whatsapp.app_secret' => null]);

        $user = User::factory()->create(['whatsapp_phone' => '201234567890']);

        $this->mock(WhatsAppService::class)
            ->shouldReceive('verifySignature')
            ->andReturn(true);

        $this->postJson('/webhooks/whatsapp', $this->payload('201234567890', 'Hello'));

        Queue::assertPushed(ProcessWhatsAppMessageJob::class, function ($job) use ($user) {
            return $job->userId === $user->id
                && $job->phone === '201234567890'
                && $job->text === 'Hello';
        });
    }

    public function test_empty_messages_array_returns_ok(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        $this->mock(WhatsAppService::class)
            ->shouldReceive('verifySignature')
            ->andReturn(true);

        $response = $this->postJson('/webhooks/whatsapp', [
            'object' => 'whatsapp_business_account',
            'entry' => [['changes' => [['value' => ['messages' => []]]]]],
        ]);

        $response->assertStatus(200);
    }

    public function test_image_messages_are_ignored(): void
    {
        Queue::fake();
        config(['services.whatsapp.app_secret' => null]);

        User::factory()->create(['whatsapp_phone' => '201234567890']);

        $this->mock(WhatsAppService::class)
            ->shouldReceive('verifySignature')
            ->andReturn(true);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '201234567890',
                            'type' => 'image',
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/webhooks/whatsapp', $payload)->assertStatus(200);

        Queue::assertNothingPushed();
    }

    public function test_audio_message_dispatches_job_with_media_id(): void
    {
        Queue::fake();
        config(['services.whatsapp.app_secret' => null]);

        $user = User::factory()->create(['whatsapp_phone' => '201234567890']);

        $this->mock(WhatsAppService::class)
            ->shouldReceive('verifySignature')
            ->andReturn(true);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '201234567890',
                            'type' => 'audio',
                            'audio' => ['id' => 'media-id-123'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/webhooks/whatsapp', $payload)->assertStatus(200);

        Queue::assertPushed(ProcessWhatsAppMessageJob::class, function ($job) use ($user) {
            return $job->userId === $user->id
                && $job->phone === '201234567890'
                && $job->text === null
                && $job->audioMediaId === 'media-id-123';
        });
    }

    public function test_rate_limiting_blocks_excessive_messages(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        User::factory()->create(['whatsapp_phone' => '201234567890']);

        $whatsApp = $this->mock(WhatsAppService::class);
        $whatsApp->shouldReceive('verifySignature')->andReturn(true);
        $whatsApp->shouldReceive('send')->once()
            ->withArgs(fn ($to, $msg) => str_contains($msg, 'Too many messages'));

        for ($i = 0; $i < 11; $i++) {
            $this->postJson('/webhooks/whatsapp', $this->payload('201234567890', 'Spam'));
        }
    }
}

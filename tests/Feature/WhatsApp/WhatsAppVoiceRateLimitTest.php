<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Jobs\ProcessWhatsAppMessageJob;
use App\Models\User;
use App\Services\Location\MessageLocationHandler;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class WhatsAppVoiceRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_voice_note_blocked_once_daily_limit_reached(): void
    {
        config()->set('services.whatsapp.voice_daily_limit', 2);

        $user = User::factory()->create(['whatsapp_phone' => '15551234567']);

        // Prime the counter at the limit.
        Cache::put("whatsapp:voice-daily:{$user->id}", 2, now()->addDay());

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('send')
            ->once()
            ->with('15551234567', Mockery::pattern('/voice-note limit/i'));
        // downloadAudio must NOT be called when blocked.
        $whatsApp->shouldNotReceive('downloadAudio');

        $locationHandler = Mockery::mock(MessageLocationHandler::class);

        $job = new ProcessWhatsAppMessageJob(
            userId: $user->id,
            phone: '15551234567',
            text: null,
            audioMediaId: 'media-abc',
        );

        $job->handle($whatsApp, $locationHandler);

        // Counter unchanged (blocked before increment path that writes)
        $this->assertSame(2, (int) Cache::get("whatsapp:voice-daily:{$user->id}"));
    }

    public function test_voice_note_disabled_when_limit_is_zero(): void
    {
        config()->set('services.whatsapp.voice_daily_limit', 0);

        $user = User::factory()->create(['whatsapp_phone' => '15551234567']);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('send')
            ->once()
            ->with('15551234567', Mockery::pattern('/disabled/i'));
        $whatsApp->shouldNotReceive('downloadAudio');

        $locationHandler = Mockery::mock(MessageLocationHandler::class);

        (new ProcessWhatsAppMessageJob(
            userId: $user->id,
            phone: '15551234567',
            text: null,
            audioMediaId: 'media-abc',
        ))->handle($whatsApp, $locationHandler);
    }

    public function test_voice_note_increments_counter_when_allowed(): void
    {
        config()->set('services.whatsapp.voice_daily_limit', 10);

        $user = User::factory()->create(['whatsapp_phone' => '15551234567']);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('downloadAudio')
            ->once()
            ->with('media-abc')
            ->andReturn(null); // force early return after counter is incremented

        $locationHandler = Mockery::mock(MessageLocationHandler::class);

        (new ProcessWhatsAppMessageJob(
            userId: $user->id,
            phone: '15551234567',
            text: null,
            audioMediaId: 'media-abc',
        ))->handle($whatsApp, $locationHandler);

        $this->assertSame(1, (int) Cache::get("whatsapp:voice-daily:{$user->id}"));
    }
}

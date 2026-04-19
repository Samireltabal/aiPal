<?php

declare(strict_types=1);

namespace Tests\Feature\Telegram;

use App\Jobs\ProcessTelegramMessageJob;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class TelegramWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.webhook_secret' => 'test-secret']);
    }

    private function makeUpdate(string $chatId, string $text): array
    {
        return [
            'message' => [
                'chat' => ['id' => $chatId],
                'from' => ['first_name' => 'Test'],
                'text' => $text,
            ],
        ];
    }

    private function webhookPost(array $update, string $secret = 'test-secret'): TestResponse
    {
        return $this->postJson('/webhooks/telegram', $update, [
            'X-Telegram-Bot-Api-Secret-Token' => $secret,
        ]);
    }

    public function test_returns_401_for_invalid_secret(): void
    {
        $this->postJson('/webhooks/telegram', $this->makeUpdate('123', 'hi'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
        ])->assertStatus(401);
    }

    public function test_start_command_sends_chat_id_to_user(): void
    {
        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('send')
            ->once()
            ->with('777', Mockery::containsString('777'));

        $this->app->instance(TelegramService::class, $telegram);

        $this->webhookPost($this->makeUpdate('777', '/start'))->assertStatus(200);
    }

    public function test_unlinked_chat_id_receives_link_instructions(): void
    {
        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('send')
            ->once()
            ->with('999', Mockery::containsString('not linked'));

        $this->app->instance(TelegramService::class, $telegram);

        $this->webhookPost($this->makeUpdate('999', 'hello'))->assertStatus(200);
    }

    public function test_dispatches_job_for_linked_user(): void
    {
        Queue::fake();

        $user = User::factory()->create(['telegram_chat_id' => '12345']);

        $this->webhookPost($this->makeUpdate('12345', 'What is the weather?'))->assertStatus(200);

        Queue::assertPushed(ProcessTelegramMessageJob::class, function ($job) use ($user): bool {
            return $job->userId === $user->id
                && $job->chatId === '12345'
                && $job->text === 'What is the weather?';
        });
    }

    public function test_ignores_update_with_no_message(): void
    {
        Queue::fake();

        $this->postJson('/webhooks/telegram', ['edited_message' => []], [
            'X-Telegram-Bot-Api-Secret-Token' => 'test-secret',
        ])->assertStatus(200);

        Queue::assertNothingPushed();
    }

    public function test_rate_limits_excessive_messages(): void
    {
        Queue::fake();

        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('send')->once()->with('888', Mockery::containsString('Too many'));

        $this->app->instance(TelegramService::class, $telegram);

        User::factory()->create(['telegram_chat_id' => '888']);

        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit('telegram:888', 60);
        }

        $this->webhookPost($this->makeUpdate('888', 'spam'))->assertStatus(200);

        Queue::assertNothingPushed();
    }
}

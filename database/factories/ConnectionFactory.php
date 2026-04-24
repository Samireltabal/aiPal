<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Connection;
use App\Models\Context;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Connection>
 */
class ConnectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'context_id' => Context::factory(),
            'provider' => Connection::PROVIDER_GOOGLE,
            'capabilities' => [Connection::CAPABILITY_MAIL, Connection::CAPABILITY_CALENDAR],
            'label' => fake()->safeEmail(),
            'identifier' => fake()->uuid(),
            'credentials' => ['access_token' => 'test-access', 'refresh_token' => 'test-refresh'],
            'is_default' => true,
            'enabled' => true,
            'metadata' => null,
            'last_synced_at' => null,
        ];
    }

    public function google(): static
    {
        return $this->state(fn () => [
            'provider' => Connection::PROVIDER_GOOGLE,
            'capabilities' => [Connection::CAPABILITY_MAIL, Connection::CAPABILITY_CALENDAR],
        ]);
    }

    public function telegram(string $chatId = '123456789'): static
    {
        return $this->state(fn () => [
            'provider' => Connection::PROVIDER_TELEGRAM,
            'capabilities' => [Connection::CAPABILITY_CHAT],
            'identifier' => $chatId,
            'label' => 'Telegram',
            'credentials' => null,
        ]);
    }

    public function whatsapp(string $phone = '15551234567'): static
    {
        return $this->state(fn () => [
            'provider' => Connection::PROVIDER_WHATSAPP,
            'capabilities' => [Connection::CAPABILITY_CHAT],
            'identifier' => $phone,
            'label' => 'WhatsApp',
            'credentials' => null,
        ]);
    }

    public function inboundEmail(?string $token = null): static
    {
        $token ??= bin2hex(random_bytes(16));

        return $this->state(fn () => [
            'provider' => Connection::PROVIDER_INBOUND_EMAIL,
            'capabilities' => [Connection::CAPABILITY_MAIL],
            'identifier' => $token,
            'label' => 'Inbound Email',
            'credentials' => null,
        ]);
    }

    public function forContext(Context $context): static
    {
        return $this->state(fn () => [
            'user_id' => $context->user_id,
            'context_id' => $context->id,
        ]);
    }
}

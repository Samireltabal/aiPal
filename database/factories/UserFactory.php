<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\Context;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Attach a default Personal context after creation. Used by tests that
     * expect the post-refactor model (contexts required).
     */
    public function withDefaultContext(): static
    {
        return $this->afterCreating(function (User $user): void {
            if (! $user->contexts()->where('is_default', true)->exists()) {
                Context::factory()->personal()->create(['user_id' => $user->id]);
            }
        });
    }

    /**
     * Attach a Work context named $name.
     */
    public function withWorkContext(string $name = 'Acme Corp'): static
    {
        return $this->afterCreating(function (User $user) use ($name): void {
            Context::factory()->work($name)->create(['user_id' => $user->id]);
        });
    }

    public function withGoogleConnection(?string $email = null): static
    {
        return $this->afterCreating(function (User $user) use ($email): void {
            $context = $this->ensureDefaultContext($user);

            Connection::factory()->google()->create([
                'user_id' => $user->id,
                'context_id' => $context->id,
                'label' => $email ?? $user->email,
                'identifier' => $email ?? $user->email,
            ]);
        });
    }

    public function withTelegramConnection(string $chatId = '123456789'): static
    {
        return $this->afterCreating(function (User $user) use ($chatId): void {
            $context = $this->ensureDefaultContext($user);

            Connection::factory()->telegram($chatId)->create([
                'user_id' => $user->id,
                'context_id' => $context->id,
            ]);
        });
    }

    public function withWhatsAppConnection(string $phone = '15551234567'): static
    {
        return $this->afterCreating(function (User $user) use ($phone): void {
            $context = $this->ensureDefaultContext($user);

            Connection::factory()->whatsapp($phone)->create([
                'user_id' => $user->id,
                'context_id' => $context->id,
            ]);
        });
    }

    public function withInboundEmailConnection(?string $token = null): static
    {
        return $this->afterCreating(function (User $user) use ($token): void {
            $context = $this->ensureDefaultContext($user);

            Connection::factory()->inboundEmail($token)->create([
                'user_id' => $user->id,
                'context_id' => $context->id,
            ]);
        });
    }

    private function ensureDefaultContext(User $user): Context
    {
        return $user->contexts()->where('is_default', true)->first()
            ?? Context::factory()->personal()->create(['user_id' => $user->id]);
    }
}
